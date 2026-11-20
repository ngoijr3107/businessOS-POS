<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeNumericInput
{
    private array $keys = ['amount','cost_price','selling_price','credit_limit','opening_balance','unit_price','discount','tax_rate'];

    public function handle(Request $request, Closure $next)
    {
        $data = $this->normalize($request->all());
        $request->replace($data);
        return $next($request);
    }

    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) { $data[$key] = $this->normalize($value); continue; }
            if (in_array($key, $this->keys, true) && is_string($value)) {
                $clean = preg_replace('/[^0-9,.-]/', '', trim($value));
                if ($clean !== null) {
                    // Support 20,000.50 and 20000.50. Comma is treated as a grouping separator.
                    $data[$key] = str_replace(',', '', $clean);
                }
            }
        }
        return $data;
    }
}
