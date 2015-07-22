<?php

namespace Fakeapp\Site\Model;

use FOF30\Tests\Stubs\Model\ModelStub;

class Foobar extends ModelStub
{
    /**
     * This method is used in {@link CallbackTest::testGetCallbackResults()} to test the callback
     * to a class method
     *
     * @param $data
     *
     * @return array
     */
    public function formCallback($data)
    {
        return $data;
    }
}