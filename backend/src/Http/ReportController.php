<?php

declare(strict_types=1);

namespace App\Http;

use App\Decorators\ConcreteStudentReport;
use App\Services\DecoratorBuilder;
use App\Services\StudentService;
use InvalidArgumentException;

class ReportController
{
    private const DECORATOR_LABELS = [
        'mention' => 'Mention',
        'classement' => 'Classement',
        'cache' => 'Cache',
        'logging' => 'Logging',
    ];

    private StudentService $studentService;

    private DecoratorBuilder $decoratorBuilder;

    public function __construct(
        ?StudentService $studentService = null,
        ?DecoratorBuilder $decoratorBuilder = null
    ) {
        $this->studentService = $studentService ?? new StudentService();
        $this->decoratorBuilder = $decoratorBuilder ?? new DecoratorBuilder();
    }

    public function getStudents(): array
    {
        return $this->studentService->getAll();
    }

    public function getReport(array $payload): array
    {
        $studentId = filter_var($payload['studentId'] ?? null, FILTER_VALIDATE_INT);
        if ($studentId === false || $studentId < 1) {
            throw new InvalidArgumentException('studentId must be a positive integer.');
        }

        $decorators = $payload['decorators'] ?? [];
        if (!is_array($decorators) || array_values($decorators) !== $decorators) {
            throw new InvalidArgumentException('decorators must be a list of decorator names.');
        }

        foreach ($decorators as $decorator) {
            if (!is_string($decorator) || !isset(self::DECORATOR_LABELS[$decorator])) {
                throw new InvalidArgumentException('The request contains an unknown decorator.');
            }
        }

        if (count($decorators) !== count(array_unique($decorators))) {
            throw new InvalidArgumentException('A decorator cannot be selected more than once.');
        }

        $startedAt = microtime(true);
        $report = $this->decoratorBuilder
            ->build($decorators, new ConcreteStudentReport())
            ->getReport($studentId);

        $decoratorChain = ['Base'];
        foreach ($decorators as $decorator) {
            $decoratorChain[] = self::DECORATOR_LABELS[$decorator];
        }

        $response = [
            'student' => $report['student'],
            'grades' => $report['grades'],
            'executionTimeMs' => (microtime(true) - $startedAt) * 1000,
            'decoratorChain' => $decoratorChain,
        ];

        foreach ([
            'moyenne' => 'moyenne',
            'mention' => 'mention',
            'rang' => 'rang',
            'taille_promotion' => 'taillePromotion',
            'cache_hit' => 'cacheHit',
        ] as $source => $target) {
            if (array_key_exists($source, $report)) {
                $response[$target] = $report[$source];
            }
        }

        if (in_array('logging', $decorators, true) && array_key_exists('logs', $report)) {
            $response['logs'] = $report['logs'];
        }

        return $response;
    }
}
