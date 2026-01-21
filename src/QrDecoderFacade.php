<?php

namespace Mapo89\QrDecoder;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mapo89\QrDecoder\Skeleton\SkeletonClass
 */
class QrDecoderFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'qr-decoder';
    }
}
