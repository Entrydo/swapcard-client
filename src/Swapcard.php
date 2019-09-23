<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Swapcard;

use Doctrine\ORM\EntityManagerInterface;
use Entrydo\Domain\Model\Ticket\Ticket;
use Entrydo\Domain\Model\Ticket\TicketId;
use Entrydo\Domain\Model\Ticket\TicketRepository;
use GuzzleHttp\Exception\ClientException;
use Nette\Utils\Json;
use Nette\Utils\Validators;
use Psr\Log\LoggerInterface;

class Swapcard
{
    /** @var TicketRepository */
    private $ticketRepository;

    /** @var bool */
    private $testMode;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    private $ignoreDuplicates = TRUE;

    /** @var SwapcardFieldSearcher */
    private $fieldSearcher;

    public function __construct(
        bool $testMode,
        TicketRepository $ticketRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        SwapcardFieldSearcher $fieldSearcher
    ){
        $this->ticketRepository = $ticketRepository;
        $this->testMode = $testMode;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->fieldSearcher = $fieldSearcher;
    }

    public function saveTicketAttendee(TicketId $ticketId): ?int
    {
        $ticket = $this->ticketRepository->ticketOfId($ticketId);
        $order = $ticket->ofOrder();
        $event = $ticket->variant()->event();
        $swapcardApiKey = $event->getSwapcardApiKey();

        if ($swapcardApiKey === null) {
            return null;
        }

        if ($ticket->isCanceled() || ($order && ! $order->isPaid()) || $ticket->isProcessedWithSwapcard()) {
            $this->logger->debug('Skipped Swapcard pairing - ticket cancelled/not paid/already processed', [
                'ticketId' => (string) $ticketId,
            ]);
            return null;
        }

        if (! Validators::isEmail((string) $ticket->email())) {
            $this->processTicket($ticket, null);
            $this->logger->debug('Skipped Swapcard pairing - invalid email', [
                'ticketId' => (string) $ticketId,
            ]);
            return null;
        }

        if (! $ticket->firstName() || ! $ticket->lastName() || ! $ticket->email()) {
            $this->processTicket($ticket, null);
            $this->logger->debug('Skipped Swapcard pairing - missing firstname/lastname/email', [
                'ticketId' => (string) $ticketId,
            ]);
            return null;
        }

        $client = new Client($swapcardApiKey, $this->testMode);

        $request = new AttendeeRequest(
            $ticket->firstName(),
            $ticket->lastName(),
            (string) $ticket->email(),
            'en',
            null,
            $this->fieldSearcher->search($ticketId, SwapcardField::JOB_TITLE) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::JOB_TITLE_2) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::COMPANY) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::KEYWORDS) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::PHOTO) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::MOBILE_PHONE) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::LANDLINE_PHONE) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::ADDRESS) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::ZIP_CODE) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::CITY) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::COUNTRY) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::WEBSITE) ?: NULL,
            $this->fieldSearcher->search($ticketId, SwapcardField::BIOGRAPHY) ?: NULL,
            [
                'badge_codes' => [(string) $ticket->code()],
                'pin_code' => (string) $ticket->code(),
            ]
        );

        try {
            $attendeeId = $client->sendAttendeeRequest($request);
        }
        catch (ClientException $e) {
            $response = $e->getResponse();
            $attendeeId = null;

            if ($response) {
                $result = Json::decode($response->getBody()->getContents());

                if ($result->status !== 400 || $result->errors[0] !== 'email: A user already exists with this value') {
                    $this->logger->error('Adding swapcard attendee error', ['exception' => $e, 'ticketId' => (string) $ticket->id()]);

                    return null;
                }

                if ($this->ignoreDuplicates === FALSE) {
                    $attendeeId = $result->data->id;
                }
            } else {
                $this->logger->error('Adding swapcard attendee error', ['exception' => $e, 'ticketId' => (string) $ticket->id()]);

                return null;
            }
        }

        $this->processTicket($ticket, $attendeeId);

        return $attendeeId;
    }

    private function processTicket(Ticket $ticket, ?int $attendeeId): void
    {
        $ticket->processSwapcardAttendee($attendeeId);
        $this->ticketRepository->save($ticket);

        $this->entityManager->flush();
    }
}
