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
            })->first(); // grab the first element of non vendor/ calls

            // $bindings = implode(", ", $query->bindings); // format the bindings as string
            // Log::debug("Sql: $query->sql\nBindings: $bindings\nTime: $query->time\nFile: {$location['file']}\nLine: {$location['line']}");

            $sql = $this->interpolateQuery($query->sql, $query->bindings);

            Log::debug("[SQL {$query->time}ms] \"{$sql};\" {$location['file']}:{$location['line']}");
        });
    }

    protected function interpolateQuery($query, $bindings)
    {
        $keys = array();
        $values = $bindings;

        # build a regular expression for each parameter
        foreach ($bindings as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if ($value instanceof \DateTime) {
                $values[$key] = $value->format('\'Y-m-d H:i:s\'');
            }

            if (is_string($value))
                $values[$key] = "'" . $value . "'";

            if (is_array($value))
                $values[$key] = "'" . implode("','", $value) . "'";

            if (is_null($value))
                $values[$key] = 'NULL';
        }

        return preg_replace($keys, $values, $query);
    }
}
