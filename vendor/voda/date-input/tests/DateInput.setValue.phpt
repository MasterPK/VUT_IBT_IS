<?php
declare(strict_types=1);

use Vodacek\Forms\Controls\DateInput;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

test(static function() { // valid submitted value
	$control = new DateInput('date', DateInput::TYPE_DATE);
	$control->setValue('2014-02-14');
	Assert::equal(new DateTimeImmutable('2014-02-14 00:00:00'), $control->getValue());
});

test(static function() { // null value
	$control = new DateInput('date', DateInput::TYPE_DATE);
	$control->setValue(null);
	Assert::equal(null, $control->getValue());
});

test(static function() { // no value
	$control = new DateInput('date', DateInput::TYPE_DATE);
	$control->setValue('');
	Assert::equal(null, $control->getValue());
});

test(static function() { // DateTimeImmutable & DateInterval values
	$control = new DateInput('date', DateInput::TYPE_TIME);
	$control->setValue(new DateTimeImmutable('1970-01-01 12:13:14'));
	Assert::equal('12:13:14', $control->getValue()->format('H:i:s'));
	$control->setValue(new DateInterval('PT12H13M14S'));
	Assert::equal('12:13:14', $control->getValue()->format('H:i:s'));
});
