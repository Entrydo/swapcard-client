<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Swapcard;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Entrydo\Domain\Model\Ticket\Input\TicketInputValue;
use Entrydo\Domain\Model\Ticket\TicketId;

class SwapcardFieldSearcher
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return mixed|null
     */
    public function search(TicketId $ticketId, string $swapcardField)
    {
        try {
            /** @var TicketInputValue $inputValue */
            $inputValue = $this->entityManager->createQueryBuilder()
                ->select('tiv')
                ->from(TicketInputValue::class, 'tiv')
                ->where('tiv.ticket = :ticket')
                ->setParameter('ticket', $ticketId)
                ->join('tiv.ticketInput', 'ti')
                ->andWhere('ti.swapcardField = :swapcardField')
                ->setParameter('swapcardField', $swapcardField)
                ->getQuery()
                ->getSingleResult();

            return $inputValue->value()->value();
        }
        catch (NoResultException $e) {
            return null;
        }
    }
}
