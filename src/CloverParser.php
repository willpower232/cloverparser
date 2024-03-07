<?php

namespace WillPower232\CloverParser;

use SimpleXMLElement;

class CloverParser
{
    /** @var array<string> */
    protected array $files = [];

    /** @var array<SimpleXMLElement> */
    protected array $xmlInstances = [];

    /** @var array<string,int> */
    protected array $totals = [];

    /** @var array<string,int> */
    protected array $coveredTotals = [];

    private bool $hasCalculatedTotals = false;

    public function addFile(string $pathToFile): self
    {
        if (!file_exists($pathToFile)) {
            throw new \RuntimeException('Clover file is not present');
        }

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \Exception($errstr, $errno);
        });

        try {
            $this->xmlInstances[] = new SimpleXMLElement($pathToFile, 0, true);
        } catch (\Exception $e) {
            restore_error_handler();
            throw new \RuntimeException('Unable to process Clover file', 0, $e);
        }

        restore_error_handler();

        $this->files[] = $pathToFile;

        $this->hasCalculatedTotals = false;

        return $this;
    }

    protected function reset(): void
    {
        $this->totals = [
            'methods' => 0,
            'conditionals' => 0,
            'statements' => 0,
        ];

        $this->coveredTotals = [
            'coveredmethods' => 0,
            'coveredconditionals' => 0,
            'coveredstatements' => 0,
        ];

        $this->hasCalculatedTotals = false;
    }

    public function calculateTotals(): self
    {
        $this->reset();

        foreach ($this->xmlInstances as $cloverXML) {
            foreach ($cloverXML->xpath('//project') as $project) {
                // no other way of seeing if the element exists?
                if ($project->metrics->count() > 0) {
                    $this->incrementTotalsWithMetrics($project->metrics);

                    continue;
                }
            }
        }

        $this->hasCalculatedTotals = true;

        return $this;
    }

    public function totalsCalculated(): bool
    {
        return $this->hasCalculatedTotals;
    }

    protected function incrementTotalsWithMetrics(SimpleXMLElement $metrics): void
    {
        $this->totals['methods'] += (int) $metrics['methods'];
        $this->coveredTotals['coveredmethods'] += (int) $metrics['coveredmethods'];
        $this->totals['conditionals'] += (int) $metrics['conditionals'];
        $this->coveredTotals['coveredconditionals'] += (int) $metrics['coveredconditionals'];
        $this->totals['statements'] += (int) $metrics['statements'];
        $this->coveredTotals['coveredstatements'] += (int) $metrics['coveredstatements'];
    }

    public function getPercentage(): float
    {
        if ($this->totalsCalculated() === false) {
            $this->calculateTotals();
        }

        $totals = array_sum($this->totals);

        if ($totals === 0) {
            return 0;
        }

        $coveredTotals = array_sum($this->coveredTotals);

        return ($coveredTotals / $totals) * 100;
    }
}
