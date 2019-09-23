<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Swapcard;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Entrydo\Domain\Model\Ticket\TicketId;

class SwapcardPinCodeRepository
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getTicketPinCode(TicketId $ticketId): ?SwapcardPinCode
    {
        try {
            return $this->entityManager->createQueryBuilder()
                ->from(SwapcardPinCode::class, 'p')
                ->select('p')
                ->where('p.ticket = :ticketId')
                ->setParameter('ticketId', $ticketId, 'ticket_id')
                ->getQuery()
                ->getSingleResult();
        }
        catch (NoResultException $e) {
            return null;
        }
    }

    public function save(SwapcardPinCode $pinCode): void
    {
        $this->entityManager->persist($pinCode);
    }
}
