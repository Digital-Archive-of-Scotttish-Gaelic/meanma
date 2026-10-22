<?php

declare(strict_types=1);

namespace Tests\Unit;

use models\corpus_search;
use models\frequency;
use PHPUnit\Framework\TestCase;

final class RequestCharacterizationTest extends TestCase
{
    public function testCorpusSearchPreservesPaginationAndSearchParameters(): void
    {
        $database = new \stdClass();
        $search = new corpus_search([
            'id' => '42',
            'term' => 'facal',
            'pp' => 25,
            'page' => 3,
            'mode' => 'wordform',
            'case' => 'sensitive',
            'accent' => 'insensitive',
            'lenition' => 'sensitive',
            'view' => 'database',
            'order' => 'dateDesc',
        ], true, $database);

        self::assertSame($database, $search->getDB());
        self::assertSame('42', $search->getId());
        self::assertSame('facal', $search->getTerm());
        self::assertSame(25, $search->getPerPage());
        self::assertSame(3, $search->getPage());
        self::assertSame('wordform', $search->getMode());
        self::assertSame('sensitive', $search->getCase());
        self::assertSame('insensitive', $search->getAccent());
        self::assertSame('sensitive', $search->getLenition());
        self::assertSame('database', $search->getView());
        self::assertSame('dateDesc', $search->getOrder());
    }

    public function testCorpusSearchAppliesExistingDefaultsAndModeFallback(): void
    {
        $search = new corpus_search([
            'mode' => 'unexpected',
            'case' => 'insensitive',
            'accent' => 'sensitive',
            'lenition' => 'insensitive',
        ], true, new \stdClass());

        self::assertSame(10, $search->getPerPage());
        self::assertSame(1, $search->getPage());
        self::assertSame('headword', $search->getMode());
        self::assertSame('corpus', $search->getView());
        self::assertSame('random', $search->getOrder());
    }

    public function testFrequencyRequestDefaultsAreStable(): void
    {
        self::assertSame([
            'type' => 'lemma',
            'query' => ['start' => 1, 'limit' => 50, 'order' => 'desc'],
        ], frequency::normalizeRequestParameters());
    }

    public function testFrequencyRequestBoundsAndFiltersAreStable(): void
    {
        self::assertSame([
            'type' => 'word-form',
            'query' => [
                'pos' => 'noun',
                'min' => 1,
                'max' => 20,
                'start' => 1,
                'limit' => 500,
                'order' => 'asc',
            ],
        ], frequency::normalizeRequestParameters([
            'type' => 'word-form',
            'pos' => 'noun',
            'min' => 0,
            'max' => 20,
            'start' => -4,
            'limit' => 900,
            'order' => 'asc',
        ]));
    }
}
