<?php declare (strict_types=1);

namespace Entrydo\Infrastructure\Swapcard;

use Entrydo\Domain\Model\Entity;
use Entrydo\Domain\Model\Event\Event;
use Entrydo\Domain\Model\Ticket\Ticket;

class SwapcardPinCode implements Entity
{
    /** @var SwapcardPinCodeId */
    private $pinCodeId;

    /** @var string */
    private $code;

    /** @var \DateTimeImmutable|null */
    private $usedAt;

    /** @var Ticket|null */
    private $ticket;

    /** @var Event */
    private $event;

    public function __construct(SwapcardPinCodeId $pinCodeId, Event $event, string $code)
    {
        $this->pinCodeId = $pinCodeId;
        $this->code = $code;
        $this->event = $event;
    }

    public function id(): SwapcardPinCodeId
    {
        return $this->pinCodeId;
    }

    public function use(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $this->usedAt = new \DateTimeImmutable();
    }

    public function usedByTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function ofEvent(): Event
    {
        return $this->event;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }
}
