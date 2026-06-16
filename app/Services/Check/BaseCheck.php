<?php

namespace App\Services\Check;

use App\Models\Part\Part;
use App\Services\Check\Contracts\CheckItem;
use App\Services\Parser\ParsedPartCollection;
use App\Services\Check\Contracts\PartDataAdapter;
use App\Services\Check\Adapters\PartModelAdapter;
use App\Services\Check\Adapters\ParsedPartAdapter;
use InvalidArgumentException;
use UnexpectedValueException;

abstract class BaseCheck
{

    public bool $stopOnError = false;

    protected PartDataAdapter $part;

    protected function supports(): array
    {
        return [
            Part::class,
            ParsedPartCollection::class,
        ];
    }

    public function run(Part|ParsedPartCollection $subject): CheckMessageCollection
    {
        $this->part = $this->resolveAdapter($subject);
        $results = new CheckMessageCollection();

        foreach ($this->check() as $result) {
            if (! $result instanceof CheckMessage) {
                throw new UnexpectedValueException(
                    sprintf('%s::check() must yield CheckMessage instances', static::class)
                );
            }

            $results->push($result);
        }

        return $results;
    }

    abstract protected function check(): iterable;

    protected function resolveAdapter(Part|ParsedPartCollection $subject): PartDataAdapter
    {
        if (! in_array($subject::class, $this->supports(), true)) {
            throw new InvalidArgumentException(class_basename(static::class) . " does not support " . $subject::class);
        }

        return match (true) {
            $subject instanceof Part => new PartModelAdapter($subject),
            $subject instanceof ParsedPartCollection => new ParsedPartAdapter($subject),
        };
    }

    protected function error(CheckItem $check, ?int $line_number = null, ?string $value = null, ?string $type = null, ?string $text = null): CheckMessage
    {
        return CheckMessage::fromArray(compact( 'check', 'line_number', 'value', 'type', 'text'));
    }
}
