<?php

class StatusTest extends \Iankibet\Shbackend\Tests\TestCase
{
    public function test_status_flips(){
        $status1 = [
            1 => "complete",
            2 => "incomplete"
        ];

        $status2 = [
            "complete" => 1,
            "incomplete" => 2
        ];

        $res = \Iankibet\Shbackend\App\Repositories\ShRepository::translateStatus("complete", $status1);
        $this->assertEquals(1, $res);
    }

}
