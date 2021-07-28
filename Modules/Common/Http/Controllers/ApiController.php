<?php

namespace Modules\Common\Http\Controllers;

use Modules\Common\Http\Controllers\Controller;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    const VERSION_API = '1.0.0';

    // Code for API
    const CODE_SUCCESS = '000';
    const CODE_LOGIN_FAIL = '013';

    const CODE_EXCEPTION_FROM_SERVER = '900';
    const CODE_EXCEPTION_OTHER = '901';

    public function __construct()
    {
    }

    /**
     * @param $data
     * @param int $statusCode
     * @param string $code
     * @param array $messages
     * @return JsonResponse
     */
    public function responseData($data, int $statusCode = 200, string $code = self::CODE_SUCCESS, array $messages = []): JsonResponse
    {
        $data_format = [
            'version' => self::VERSION_API,
            'status' => [
                'code' => $code,
                'message' => $messages,
                'api' => request()->path()
            ],
            'result' => empty($data) ? (object)[] : $data
        ];

        return response()->json($data_format, $statusCode);
    }

    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();

        $dataFormatted = [
            'message' => 'The given data was invalid.',
            'errors' => $errors
        ];

        throw new HttpResponseException(
            response()->json($dataFormatted, JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
