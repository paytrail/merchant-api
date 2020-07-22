<?php

declare(strict_types=1);

namespace Paytrail\MerchantApi;

/**
 * @author Paytrail <tech@paytrail.com>
 */
class Success extends Response
{
    public function __construct(string $content)
    {
        $this->content = $content;
    }
}
