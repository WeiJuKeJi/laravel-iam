<?php

namespace WeiJuKeJi\LaravelIam\Tests\Unit\Services;

use WeiJuKeJi\LaravelIam\Exceptions\DepartmentMoveException;
use WeiJuKeJi\LaravelIam\Models\Department;
use WeiJuKeJi\LaravelIam\Services\DepartmentMoveService;
use WeiJuKeJi\LaravelIam\Tests\TestCase;

class DepartmentMoveServiceTest extends TestCase
{
    protected DepartmentMoveService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DepartmentMoveService();
    }

    /** @test */
    public function it_can_move_department_before_target(): void
    {
        $dept1 = Department::create(['name' => 'Dept 1']);
        $dept2 = Department::create(['name' => 'Dept 2']);

        $this->service->move($dept2, 'before', $dept1->id);

        // 验证 dept2 在 dept1 之前
        $this->assertDatabaseHas('iam_departments', [
            'id' => $dept2->id,
            'name' => 'Dept 2',
        ]);
    }

    /** @test */
    public function it_can_move_department_after_target(): void
    {
        $dept1 = Department::create(['name' => 'Dept 1']);
        $dept2 = Department::create(['name' => 'Dept 2']);

        $this->service->move($dept2, 'after', $dept1->id);

        $this->assertDatabaseHas('iam_departments', [
            'id' => $dept2->id,
            'name' => 'Dept 2',
        ]);
    }

    /** @test */
    public function it_can_move_department_inside_parent(): void
    {
        $parent = Department::create(['name' => 'Parent']);
        $dept = Department::create(['name' => 'Department']);

        $this->service->move($dept, 'inside', null, $parent->id);

        $dept->refresh();
        $this->assertEquals($parent->id, $dept->parent_id);
    }

    /** @test */
    public function it_can_move_department_to_root(): void
    {
        $parent = Department::create(['name' => 'Parent']);
        $dept = Department::create(['name' => 'Child', 'parent_id' => $parent->id]);

        $this->service->move($dept, 'inside', null, null);

        $dept->refresh();
        $this->assertNull($dept->parent_id);
    }

    /** @test */
    public function it_throws_exception_when_target_not_found(): void
    {
        $dept = Department::create(['name' => 'Department']);

        $this->expectException(DepartmentMoveException::class);
        $this->expectExceptionMessage('目标部门不存在');

        $this->service->move($dept, 'before', 99999);
    }

    /** @test */
    public function it_throws_exception_when_moving_to_self(): void
    {
        $dept = Department::create(['name' => 'Department']);

        $this->expectException(DepartmentMoveException::class);
        $this->expectExceptionMessage('不能将部门移动到自身');

        $this->service->move($dept, 'before', $dept->id);
    }

    /** @test */
    public function it_throws_exception_when_moving_to_descendant(): void
    {
        $parent = Department::create(['name' => 'Parent']);
        $child = Department::create(['name' => 'Child', 'parent_id' => $parent->id]);

        $this->expectException(DepartmentMoveException::class);
        $this->expectExceptionMessage('不能将部门移动到其子部门下');

        $this->service->move($parent, 'inside', null, $child->id);
    }

    /** @test */
    public function it_validates_target_id_required_for_before_after(): void
    {
        $dept = Department::create(['name' => 'Department']);

        $this->expectException(DepartmentMoveException::class);
        $this->expectExceptionMessage('before/after 操作需要提供目标部门ID');

        $this->service->move($dept, 'before', null);
    }
}
