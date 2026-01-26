<?php

namespace WeiJuKeJi\LaravelIam\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kalnoy\Nestedset\NodeTrait;
use WeiJuKeJi\LaravelIam\Support\ConfigHelper;

class Department extends Model
{
    use HasFactory;
    use Filterable;
    use NodeTrait;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'manager_id',
        'sort_order',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * 构造函数，动态设置表名
     */
    public function __construct(array $attributes = [])
    {
        $this->table = ConfigHelper::table('departments');
        parent::__construct($attributes);
    }

    /**
     * 部门负责人
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(config('iam.models.user', User::class), 'manager_id');
    }

    /**
     * 部门下的员工
     */
    public function users(): HasMany
    {
        return $this->hasMany(config('iam.models.user', User::class), 'department_id');
    }

    /**
     * 模型筛选
     */
    public function modelFilter()
    {
        return $this->provideFilter(\WeiJuKeJi\LaravelIam\ModelFilters\DepartmentFilter::class);
    }

    /**
     * 查询作用域：启用的部门
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 查询作用域：根部门（顶级部门）
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 获取部门全路径名称
     * 例如：总公司/技术部/研发中心
     */
    public function getFullPathAttribute(): string
    {
        return $this->ancestors()
            ->pluck('name')
            ->push($this->name)
            ->implode(' / ');
    }

    /**
     * 获取部门层级
     */
    public function getLevelAttribute(): int
    {
        return $this->ancestors()->count();
    }

    /**
     * 检查是否为叶子节点（没有子部门）
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * 转换为前端树形结构
     */
    public function toTreeArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'manager_id' => $this->manager_id,
            'manager' => $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ] : null,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'level' => $this->level,
            'is_leaf' => $this->isLeaf(),
        ];

        if ($this->relationLoaded('children') && $this->children->isNotEmpty()) {
            $data['children'] = $this->children->map(fn($child) => $child->toTreeArray())->all();
        }

        return $data;
    }
}
