<?php


namespace MediaWiki\Tests\Unit;

use MediaWikiUnitTestCase;
use PasswordPolicyChecks;
use PHPUnit\Framework\MockObject\MockObject;
use User;

/**
 * Split from \PasswordPolicyChecksTest integration tests
 *
 * @coversDefaultClass \PasswordPolicyChecks
 */
class PasswordPolicyChecksTest extends MediaWikiUnitTestCase {

	/**
	 * @param string $name
	 * @return User|MockObject
	 */
	private function getUser( $name = 'user' ) {
		$user = $this->createMock( User::class );
		$user->method( 'getName' )->willReturn( $name );
		return $user;
	}

	/**
	 * @covers ::checkMinimalPasswordLength
	 */
	public function testCheckMinimalPasswordLength() {
		$statusOK = PasswordPolicyChecks::checkMinimalPasswordLength(
			3, // policy value
			$this->getUser(), // User
			'password'  // password
		);
		$this->assertTrue( $statusOK->isGood(), 'Password is longer than minimal policy' );
		$statusShort = PasswordPolicyChecks::checkMinimalPasswordLength(
			10, // policy value
			$this->getUser(), // User
			'password'  // password
		);
		$this->assertFalse(
			$statusShort->isGood(),
			'Password is shorter than minimal policy'
		);
		$this->assertTrue(
			$statusShort->isOK(),
			'Password is shorter than minimal policy, not fatal'
		);
	}

	/**
	 * @covers ::checkMinimumPasswordLengthToLogin
	 */
	public function testCheckMinimumPasswordLengthToLogin() {
		$statusOK = PasswordPolicyChecks::checkMinimumPasswordLengthToLogin(
			3, // policy value
			$this->getUser(), // User
			'password'  // password
		);
		$this->assertTrue( $statusOK->isGood(), 'Password is longer than minimal policy' );
		$statusShort = PasswordPolicyChecks::checkMinimumPasswordLengthToLogin(
			10, // policy value
			$this->getUser(), // User
			'password'  // password
		);
		$this->assertFalse(
			$statusShort->isGood(),
			'Password is shorter than minimum login policy'
		);
		$this->assertFalse(
			$statusShort->isOK(),
			'Password is shorter than minimum login policy, fatal'
		);
	}

	/**
	 * @covers ::checkMaximalPasswordLength
	 */
	public function testCheckMaximalPasswordLength() {
		$statusOK = PasswordPolicyChecks::checkMaximalPasswordLength(
			100, // policy value
			$this->getUser(), // User
			'password'  // password
		);
		$this->assertTrue( $statusOK->isGood(), 'Password is shorter than maximal policy' );
		$statusLong = PasswordPolicyChecks::checkMaximalPasswordLength(
			4, // policy value
			$this->getUser(), // User
			'password'  // password
		);
		$this->assertFalse( $statusLong->isGood(),
			'Password is longer than maximal policy'
		);
		$this->assertFalse( $statusLong->isOK(),
			'Password is longer than maximal policy, fatal'
		);
	}

	/**
	 * @covers ::checkPasswordCannotBeSubstringInUsername
	 */
	public function testCheckPasswordCannotBeSubstringInUsername() {
		$statusOK = PasswordPolicyChecks::checkPasswordCannotBeSubstringInUsername(
			1, // policy value
			$this->getUser(), // User
			'password'  // password
		);
		$this->assertTrue( $statusOK->isGood(), 'Password is not a substring of username' );
		$statusLong = PasswordPolicyChecks::checkPasswordCannotBeSubstringInUsername(
			1, // policy value
			$this->getUser( '123user123' ), // User
			'user'  // password
		);
		$this->assertFalse( $statusLong->isGood(), 'Password is a substring of username' );
		$this->assertTrue( $statusLong->isOK(), 'Password is a substring of username, not fatal' );
	}

	/**
	 * @covers ::checkPasswordCannotMatchDefaults
	 * @dataProvider provideCheckPasswordCannotMatchDefaults
	 */
	public function testCheckPasswordCannotMatchDefaults(
		bool $failureExpected,
		bool $policyValue,
		string $username,
		string $password
	) {
		$status = PasswordPolicyChecks::checkPasswordCannotMatchDefaults(
			$policyValue,
			$this->getUser( $username ),
			$password
		);

		if ( $failureExpected ) {
			$this->assertFalse( $status->isGood(), 'Password matches defaults list' );
			$this->assertTrue( $status->isOK(), 'Password matches default list, not fatal' );
			$this->assertTrue( $status->hasMessage( 'password-login-forbidden' ) );
		} else {
			$this->assertTrue( $status->isGood(), 'Password is not on defaults list' );
		}
	}

	public function provideCheckPasswordCannotMatchDefaults() {
		return [
			'Unique username and password' => [ false, true, 'Unique username', 'AUniquePassword' ],
			'Invalid combination' => [ true, true, 'Useruser1', 'Passpass1' ],
			'Invalid password' => [ true, true, 'Whatever username', 'ExamplePassword' ],
			'Uniques but no policy' => [ false, false, 'Unique username', 'AUniquePassword' ],
			'Invalid combination but no policy' => [ false, false, 'Useruser1', 'Passpass1' ],
			'Invalid password but no policy' => [ false, false, 'Whatever username', 'ExamplePassword' ],
		];
	}

	/**
	 * @covers ::checkPasswordNotInCommonList
	 * @dataProvider provideCommonList
	 */
	public function testCheckNotInCommonList( $expected, $password ) {
		$status = PasswordPolicyChecks::checkPasswordNotInCommonList(
			true,
			$this->getUser( 'username' ),
			$password
		);
		$this->assertSame( $expected, $status->isGood() );
	}

	public function provideCommonList() {
		return [
			[ false, 'testpass' ],
			[ false, 'password' ],
			[ false, '12345' ],
			[ true, 'DKn17egcA4' ],
			[ true, 'testwikijenkinspass' ],
		];
	}
}
