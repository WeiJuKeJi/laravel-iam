<?php

namespace WeiJuKeJi\LaravelIam\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use WeiJuKeJi\LaravelIam\Exceptions\DepartmentMoveException;
use WeiJuKeJi\LaravelIam\Models\Department;

/**
 * 部门移动服务
 *
 * 负责处理部门的移动操作，包括位置验证和树结构更新
 */
class DepartmentMoveService
{
    /**
     * 移动部门到指定位置
     *
     * @param  Department  $department  要移动的部门
     * @param  string  $position  位置：before, after, inside
     * @param  int|null  $targetId  目标部门ID
     * @param  int|null  $parentId  父部门ID（仅当 position=inside 时使用）
     * @return Department 移动后的部门
     *
     * @throws DepartmentMoveException
     */
    public function move(
        Department $department,
        string $position,
        ?int $targetId = null,
        ?int $parentId = null
    ): Department {
        // 验证移动操作的合法性
        $this->validateMove($department, $position, $targetId, $parentId);

        // 执行移动操作
        match ($position) {
            'before' => $this->moveBefore($department, $targetId),
            'after' => $this->moveAfter($department, $targetId),
            'inside' => $this->moveInside($department, $parentId),
            default => throw DepartmentMoveException::invalidMove("不支持的位置类型：{$position}"),
        };

        return $department;
    }

    /**
     * 验证移动操作的合法性
     */
    protected function validateMove(
        Department $department,
        string $position,
        ?int $targetId,
        ?int $parentId
    ): void {
        // 如果是 before/after 操作，必须提供 targetId
        if (in_array($position, ['before', 'after']) && ! $targetId) {
            throw DepartmentMoveException::invalidMove('before/after 操作需要提供目标部门ID');
        }

        // 不能移动到自身
        if ($targetId === $department->id) {
            throw DepartmentMoveException::cannotMoveToSelf();
        }

        if ($parentId === $department->id) {
            throw DepartmentMoveException::cannotMoveToSelf();
        }

        // 检查是否试图移动到子部门下
        if ($parentId && $this->isDescendant($department, $parentId)) {
            throw DepartmentMoveException::cannotMoveToDescendant();
        }

        if ($targetId && $this->isDescendant($department, $targetId)) {
            throw DepartmentMoveException::cannotMoveToDescendant();
        }
    }

    /**
     * 检查目标部门是否是当前部门的子孙部门
     */
    protected function isDescendant(Department $department, int $targetId): bool
    {
        try {
            $target = Department::findOrFail($targetId);

            return $target->isDescendantOf($department);
        } catch (ModelNotFoundException) {
            return false;
        }
    }

    /**
     * 移动到目标部门之前
     */
    protected function moveBefore(Department $department, int $targetId): void
    {
        try {
            $target = Department::findOrFail($targetId);
            $department->beforeNode($target)->save();
        } catch (ModelNotFoundException $e) {
            throw DepartmentMoveException::targetNotFound($targetId);
        } catch (\Throwable $e) {
            throw DepartmentMoveException::invalidMove("移动失败：{$e->getMessage()}");
        }
    }

    /**
     * 移动到目标部门之后
     */
    protected function moveAfter(Department $department, int $targetId): void
    {
        try {
            $target = Department::findOrFail($targetId);
            $department->afterNode($target)->save();
        } catch (ModelNotFoundException $e) {
            throw DepartmentMoveException::targetNotFound($targetId);
        } catch (\Throwable $e) {
            throw DepartmentMoveException::invalidMove("移动失败：{$e->getMessage()}");
        }
    }

    /**
     * 移动到父部门内部或设为根部门
     */
    protected function moveInside(Department $department, ?int $parentId): void
    {
        try {
            if ($parentId) {
                $parent = Department::findOrFail($parentId);
                $department->appendToNode($parent)->save();
            } else {
                $department->saveAsRoot();
            }
        } catch (ModelNotFoundException $e) {
            throw DepartmentMoveException::parentNotFound($parentId);
        } catch (\Throwable $e) {
            throw DepartmentMoveException::invalidMove("移动失败：{$e->getMessage()}");
        }
    }
}
