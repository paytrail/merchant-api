<?php
declare(strict_types=1);

namespace Paytrail\MerchantApi;

/**
 * @author Paytrail <tech@paytrail.com>
 */
class Failed extends Response
{
    public function __construct($error)
    {
        $this->error = $error;
    }
}