<?php

namespace App\Services\Exceptions;

use DomainException;

class QuoteVersionException extends DomainException
{
    public static function quoteRequiresSingleOrder(): self
    {
        return new self('Una cotización versionada debe estar vinculada a exactamente una orden de servicio.');
    }

    public static function onlyQuotesCanBeVersioned(): self
    {
        return new self('Solo los documentos de tipo cotización pueden versionarse.');
    }

    public static function onlyQuotesCanBeApproved(): self
    {
        return new self('Solo las cotizaciones pueden aprobarse.');
    }

    public static function onlyQuotesCanBeSent(): self
    {
        return new self('Solo las cotizaciones pueden enviarse.');
    }

    public static function onlyQuotesCanBeRejected(): self
    {
        return new self('Solo las cotizaciones pueden rechazarse.');
    }

    public static function quoteOrderMismatch(): self
    {
        return new self('La orden vinculada a la cotización no pertenece al mismo contexto del documento.');
    }

    public static function quoteRequiresActiveOrderContext(): self
    {
        return new self('La cotización requiere una orden activa y consistente para aprobarse.');
    }

    public static function invalidQuoteStatusForApproval(string $status): self
    {
        return new self(sprintf('La cotización no puede aprobarse desde el estado [%s].', $status));
    }

    public static function quoteCompanyMismatch(): self
    {
        return new self('La cotización y la orden asociada no pertenecen a la misma empresa.');
    }

    public static function quoteCustomerMismatch(): self
    {
        return new self('La cotización y la orden asociada no pertenecen al mismo cliente.');
    }
}
