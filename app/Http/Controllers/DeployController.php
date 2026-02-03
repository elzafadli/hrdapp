<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeployController extends Controller
{
    public function pull()
    {
        try {
            // Fix dubious ownership error if running in container
            shell_exec('git config --global --add safe.directory /app');

            $output = shell_exec('git pull https://github.com/elzafadli/hrdapp.git main 2>&1');

            return response()->json([
                'success' => true,
                'message' => 'Git pull executed',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing git pull',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
