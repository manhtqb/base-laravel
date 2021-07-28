<?php
namespace Modules\Common\Services;

use Illuminate\Support\Facades\Log;
use Modules\Common\Helpers\Helper;

class ImageService
{
    /**
     * @param string $folder
     * @param $file
     * @param string $methodGetUrl
     * @return string
     */
    public function upload(string $folder, $file, string $methodGetUrl = 'normal'): string
    {
        try {
            $filePath = Helper::uploadFile($folder, $file);

            if ($methodGetUrl === 's3') {
                $result =  Helper::getS3FullUrlFile($filePath);
            } else {
                $result =  Helper::getFullUrlFile($filePath);
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            $result = '';
        }

        return $result;
    }
}
