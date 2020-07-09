<?php
declare(strict_types=1);

namespace Paytrail\MerchantApi;

/**
 * @author Paytrail <tech@paytrail.com>
 */
abstract class Response {

    protected $content;
    protected $error;

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getError()
    {
        return $this->error;
    }
}