<?php
/**
 * This file is part of the DreamFactory Rave(tm)
 *
 * DreamFactory Rave(tm) <http://github.com/dreamfactorysoftware/rave>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use DreamFactory\Rave\Utility\ServiceHandler;
use DreamFactory\Library\Utility\Enums\Verbs;
use DreamFactory\Rave\Models\User;
use Illuminate\Support\Arr;

class ProfileResourceTest extends \DreamFactory\Rave\Testing\TestCase
{
    const RESOURCE = 'profile';

    protected $serviceId = 'user';

    protected $user1 = [
        'name'              => 'John Doe',
        'first_name'        => 'John',
        'last_name'         => 'Doe',
        'email'             => 'jdoe@dreamfactory.com',
        'password'          => 'test1234',
        'security_question' => 'Make of your first car?',
        'security_answer'   => 'mazda',
        'is_active'         => 1
    ];

    public function tearDown()
    {
        $this->deleteUser( 1 );

        parent::tearDown();
    }

    public function testNoProfileFound()
    {
        $this->setExpectedException('\DreamFactory\Rave\Exceptions\NotFoundException');
        $this->makeRequest( Verbs::GET, static::RESOURCE );
    }

    public function testGETProfile()
    {
        $user = $this->createUser( 1 );
        $userModel = User::find( $user['id'] );
        $this->be( $userModel );

        $rs = $this->makeRequest( Verbs::GET, static::RESOURCE );
        $c = $rs->getContent();

        $e = [
            'first_name'        => Arr::get( $user, 'first_name' ),
            'last_name'         => Arr::get( $user, 'last_name' ),
            'name'              => Arr::get( $user, 'name' ),
            'email'             => Arr::get( $user, 'email' ),
            'phone'             => Arr::get( $user, 'phone' ),
            'security_question' => Arr::get( $user, 'security_question' )
        ];

        $this->assertEquals( $e, $c );
    }

    public function testPOSTProfile()
    {
        $user = $this->createUser( 1 );
        $userModel = User::find( $user['id'] );
        $this->be( $userModel );

        $fName = 'Jack';
        $lName = 'Smith';
        $name = 'Jack';
        $email = 'jsmith@example.com';
        $this->user1['email'] = $email;
        $phone = '123-475-7383';
        $sQuestion = 'Foo?';
        $sAnswer = 'bar';

        $payload = [
            'first_name'        => $fName,
            'last_name'         => $lName,
            'name'              => $name,
            'email'             => $email,
            'phone'             => $phone,
            'security_question' => $sQuestion,
            'security_answer'   => $sAnswer
        ];

        $r = $this->makeRequest(Verbs::POST, static::RESOURCE, [], $payload);
        $c = $r->getContent();

        $this->assertTrue(Arr::get($c, 'success'));

        $r = $this->makeRequest(Verbs::GET, static::RESOURCE);
        $c = $r->getContent();

        $this->assertTrue(Hash::check(Arr::get($payload, 'security_answer'), $userModel->security_answer));

        unset($payload['security_answer']);
        $this->assertEquals($payload, $c);
    }

    /************************************************
     * Helper methods
     ************************************************/

    protected function createUser( $num )
    {
        $user = $this->{'user' . $num};
        $payload = json_encode( [ $user ], JSON_UNESCAPED_SLASHES );

        $this->service = ServiceHandler::getService( 'system' );
        $rs = $this->makeRequest( Verbs::POST, 'user', [ 'fields' => '*', 'related' => 'user_lookup_by_user_id' ], $payload );
        $this->service = ServiceHandler::getService( $this->serviceId );

        return $rs->getContent();
    }

    protected function deleteUser( $num )
    {
        $user = $this->{'user' . $num};
        $email = Arr::get( $user, 'email' );
        \DreamFactory\Rave\Models\User::whereEmail( $email )->delete();
    }
}