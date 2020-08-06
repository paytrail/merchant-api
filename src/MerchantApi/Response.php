<?php

declare(strict_types=1);

namespace Paytrail\MerchantApi;

/**
 * @author Paytrail <tech@paytrail.com>
 */
abstract class Response
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $error;

    public function isSuccess(): bool
    {
        return get_class($this) === Success::class;
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
