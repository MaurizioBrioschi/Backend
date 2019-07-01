<?php

namespace App\Controller\Api;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionController extends \Symfony\Bundle\TwigBundle\Controller\ExceptionController
{
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $data = [
            'message' => $exception->getMessage(),
        ];

        return new JsonResponse($data, $exception->getCode());
    }
}
