<?php

namespace Hybridly\Support;

final class CaseConverter
{
    public function convert(array $payload, string $case): array
    {
        return $this->toCase($payload, $case);
    }

    private function toCase(array $payload, string $case): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            if (\is_array($value)) {
                $value = $this->toCase($value, $case);
            }

            $key = (string) match ($case) {
                'snake' => str()->snake($key),
                'camel' => str()->camel($key),
            };

            $result[$key] = $value;
        }

        return $result;
    }
}
