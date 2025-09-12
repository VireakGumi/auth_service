<?php

if (!function_exists('res_success')) {
    function res_success($message = '', $data = [], $code = 1, $errors = [])
    {
        $responseData['result'] = true;
        $responseData['code'] = $code;
        $responseData['message'] = $message;
        $responseData['data'] = $data;
        if($errors) $responseData['errors'] = $errors;
        return response()->json($responseData, 200);
    }
}

if (!function_exists('res_fail')) {
    function res_fail($message = '', $data = [], $code = 1, $status = 200)
    {
        $responseData['result'] = false;
        $responseData['code'] = $code;
        $responseData['message'] = $message;
        $responseData['data'] = $data;
        return response()->json($responseData, $status);
    }
}

if (!function_exists('res_wentwrong')) {
    function res_wentwrong()
    {
        $responseData['result'] = false;
        $responseData['code'] = 1;
        $responseData['message'] = 'Something went wrong!';
        $responseData['data'] = [];
        return response()->json($responseData, 500);
    }
}

if (!function_exists('res_paginate')) {
    function res_paginate($paginate, $message = '', $data = [], $code = 1)
    {
        $responseData['result'] = true;
        $responseData['code'] = $code;
        $responseData['message'] = $message;
        $responseData['data'] = $data;
        $responseData['paginate'] = [
            'has_page' => $paginate->hasPages(),
            'on_first_page' => $paginate->onFirstPage(),
            'has_more_pages' => $paginate->hasMorePages(),
            'first_item' => $paginate->firstItem(),
            'last_item' => $paginate->lastItem(),
            'total' => $paginate->total(),
            'current_page' => $paginate->currentPage(),
            'last_page' => $paginate->lastPage()
        ];
        return response()->json($responseData, 200);
    }
}
