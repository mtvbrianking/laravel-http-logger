<?php

namespace Bmatovu\HttpLogger\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Database
{
    public static function logDbQueries()
    {
        DB::listen(function ($query) {
            $location = collect(debug_backtrace())->filter(function ($trace) {
                return isset($trace['file']) && !str_contains($trace['file'], 'vendor/');
            })->first();

            // $bindings = implode(", ", $query->bindings);
            // Log::debug("Sql: $query->sql\nBindings: $bindings\nTime: $query->time\nFile: {$location['file']}\nLine: {$location['line']}");

            $sql = self::interpolateQuery($query->sql, $query->bindings);

            Log::debug("[SQL {$query->time}ms] \"{$sql};\" {$location['file']}:{$location['line']}");
        });
    }

    protected static function interpolateQuery(string $query, array $bindings)
    {
        $keys = $values = [];

        foreach ($bindings as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            }

            if ($value instanceof \DateTime) {
                $values[$key] = $value->format('\'Y-m-d H:i:s\'');
                continue;
            }

            if (is_numeric($value)) {
                $values[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $values[$key] = "'" . implode("', '", $value) . "'";
                continue;
            }

            if (is_null($value)) {
                $values[$key] = 'NULL';
                continue;
            }

            $values[$key] = "'" . $value . "'";
        }

        if (empty($keys)) {
            return preg_replace_callback('/[?]/', function ($matches) use (&$values) {
                return array_shift($values);
            }, $query);
        }

        return preg_replace($keys, $values, $query);
    }
}
