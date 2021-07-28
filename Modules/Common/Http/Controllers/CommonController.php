<?php

namespace Modules\Common\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Common\Services\S3Service;

class CommonController extends Controller
{
    protected $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    public function getS3PresignedUrl(Request $request)
    {
        $fileName = $request->get('file_name');
        $presignedUrl = $this->s3Service->getPresignedUrl([
            'file_name' => $fileName
        ]);

        return response()->json([
            'status' => $presignedUrl ? 200 : 500,
            'data' => $presignedUrl
        ]);
    }
}
