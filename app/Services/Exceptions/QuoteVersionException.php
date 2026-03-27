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
}
