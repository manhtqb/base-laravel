<?php

namespace Modules\Common\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Common\Helpers\Helper;

class S3Service
{
    public function getPresignedUrl(array $params)
    {
        try {
            $s3 = \Storage::disk('s3');
            $client = $s3->getDriver()->getAdapter()->getClient();
            $expiry = config('filesystems.presigned_url_life_time');

            $command = $client->getCommand('PutObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => config('common.file_path_video_course') . '/' . Carbon::now()->getTimestamp() . '_' . $params['file_name'],
//                '@use_accelerate_endpoint' => true,
            ]);

            $request = $client->createPresignedRequest($command, $expiry);

            return (string)$request->getUri();
        } catch (\Exception $exception) {
            Log::error($exception);
            return false;
        }
    }
}
