<?php
namespace Tests\Unit\Services;

use App\Services\AbstractCommitService;
use App\Services\CommitServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AbstractVersionControlServiceTest extends TestCase
{
    #[Test]
    public function testGetCallsConnector()
    {
        $connectorMock = $this->createMock(CommitServiceInterface::class);
        $connectorMock->expects($this->once())
            ->method('get')
            ->with(100)
            ->willReturn(['commit1', 'commit2']);

        $serviceMock = $this->getMockBuilder(AbstractCommitService::class)
            ->onlyMethods(['getVersionControlService'])
            ->getMock();

        $serviceMock->expects($this->once())
            ->method('getVersionControlService')
            ->willReturn($connectorMock);

        $result = $serviceMock->get(100);

        $this->assertSame(['commit1', 'commit2'], $result);
    }

    #[Test]
    public function testViewCallsConnector()
    {
        $connectorMock = $this->createMock(CommitServiceInterface::class);
        $connectorMock->expects($this->once())
            ->method('view')
            ->with(1, 50)
            ->willReturn(['page1', 'page2']);

        $serviceMock = $this->getMockBuilder(AbstractCommitService::class)
            ->onlyMethods(['getVersionControlService'])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('getVersionControlService')
            ->willReturn($connectorMock);

        $result = $serviceMock->view(1, 50);

        $this->assertSame(['page1', 'page2'], $result);
    }
}
