<?php

namespace DebugBar\Tests;

use DebugBar\DataCollector\PDO\TracedStatement;

/**
 * Class TracedStatementTest
 * @package DebugBar\Tests
 */
class TracedStatementTest extends DebugBarTestCase
{
    /**
     * Check if query parameters are being replaced in the correct way
     * @bugFix Before fix it : select *
     *                          from geral.exame_part ep
     *                           where ep.id_exame = <1> and
     *                             ep.id_exame_situacao = <2>'
     *                            ep.id_exame_situacao = <1>_situacao
     */
    #[\ReturnTypeWillChange] public function testReplacementParamsQuery(): void
    {
        $sql = 'select *
                from geral.exame_part ep
                where ep.id_exame = :id_exame and
                      ep.id_exame_situacao = :id_exame_situacao';
        $params = [
            ':id_exame'          => 1,
            ':id_exame_situacao' => 2
        ];
        $traced = new TracedStatement($sql, $params);
        $expected = 'select *
                from geral.exame_part ep
                where ep.id_exame = <1> and
                      ep.id_exame_situacao = <2>';
        $result = $traced->getSqlWithParams();
        $this->assertEquals($expected, $result);
    }

    #[\ReturnTypeWillChange] public function testReplacementParamsContainingBackReferenceSyntaxGeneratesCorrectString(): void
    {
        $hashedPassword = '$2y$10$S3Y/kSsx8Z5BPtdd9.k3LOkbQ0egtsUHBT9EGQ.spxsmaEWbrxBW2';
        $sql = "UPDATE user SET password = :password";

        $params = [
            ':password' => $hashedPassword,
        ];

        $traced = new TracedStatement($sql, $params);

        $result = $traced->getSqlWithParams();

        $expected = sprintf('UPDATE user SET password = <%s>', $hashedPassword);

        $this->assertEquals($expected, $result);
    }

    #[\ReturnTypeWillChange] public function testReplacementParamsContainingPotentialAdditionalQuestionMarkPlaceholderGeneratesCorrectString(): void
    {
        $hasQuestionMark = "Asking a question?";
        $string = "Asking for a friend";

        $sql = "INSERT INTO questions SET question = ?, detail = ?";

        $params = [$hasQuestionMark, $string];

        $traced = new TracedStatement($sql, $params);

        $result = $traced->getSqlWithParams();

        $expected = sprintf('INSERT INTO questions SET question = <%s>, detail = <%s>', $hasQuestionMark, $string);

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams("'");

        $expected = sprintf("INSERT INTO questions SET question = '%s', detail = '%s'", $hasQuestionMark, $string);

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams('"');

        $expected = sprintf('INSERT INTO questions SET question = "%s", detail = "%s"', $hasQuestionMark, $string);

        $this->assertEquals($expected, $result);
    }

    #[\ReturnTypeWillChange] public function testReplacementParamsContainingPotentialAdditionalNamedPlaceholderGeneratesCorrectString(): void
    {
        $hasQuestionMark = "Asking a question with a :string inside";
        $string = "Asking for a friend";

        $sql = "INSERT INTO questions SET question = :question, detail = :string";

        $params = [
            ':question' => $hasQuestionMark,
            ':string'   => $string,
        ];

        $traced = new TracedStatement($sql, $params);

        $result = $traced->getSqlWithParams();

        $expected = sprintf('INSERT INTO questions SET question = <%s>, detail = <%s>', $hasQuestionMark, $string);

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams("'");

        $expected = sprintf("INSERT INTO questions SET question = '%s', detail = '%s'", $hasQuestionMark, $string);

        $this->assertEquals($expected, $result);

        $result = $traced->getSqlWithParams('"');

        $expected = sprintf('INSERT INTO questions SET question = "%s", detail = "%s"', $hasQuestionMark, $string);

        $this->assertEquals($expected, $result);
    }

    /**
     * Check if query parameters are being replaced in the correct way
     * @bugFix Before fix it : select *
     *                          from geral.person p
     *                           left join geral.contract c
     *                             on c.id_person = p.id_person
     *                           where c.status = <1> and
     *                           p.status <> :status;
     */
    #[\ReturnTypeWillChange] public function testRepeadParamsQuery(): void
    {
        $sql = 'select *
                from geral.person p
                left join geral.contract c
                  on c.id_person = p.id_person
                where c.status = :status and
                      p.status <> :status';
        $params = [
            ':status' => 1
        ];
        $traced = new TracedStatement($sql, $params);
        $expected = 'select *
                from geral.person p
                left join geral.contract c
                  on c.id_person = p.id_person
                where c.status = <1> and
                      p.status <> <1>';
        $result = $traced->getSqlWithParams();
        $this->assertEquals($expected, $result);
    }

    /**
     * Check that query parameters are being replaced only once
     * @bugFix Before fix it: select * from
     *                          `my_table` where `my_field` between
     *                           <2018-01-01> and <2018-01-01>
     */
    #[\ReturnTypeWillChange] public function testParametersAreNotRepeated(): void
    {
        $query = 'select * from `my_table` where `my_field` between ? and ?';
        $bindings = [
            '2018-01-01',
            '2020-09-01',
        ];

        $this->assertEquals(
            'select * from `my_table` where `my_field` between <2018-01-01> and <2020-09-01>',
            new TracedStatement($query, $bindings)->getSqlWithParams()
        );
    }
}
