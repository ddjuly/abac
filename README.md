# 关于abac权限管理

不装逼，不做作，不盲目滥用技术，只为实现功能，使用简单，功能齐全👻👻

# 如何使用

创建abac表，默认abac_前缀，当然你也可以改成你自己的，打命令会有提示😑
php artisan abac.create-table

中间件使用
'permission' => \Abac\Middleware\AbacPermission::class,
您也可以copy该中间件出来进行自定义，呵呵！

添加角色
\Abac::addRole(角色名);

添加权限
\Abac::addPermission(权限名);

添加权限到角色中
\Abac::addPermission2Role(角色id(int)|角色名(string), 权限id(int)|权限名(string));

添加角色到用户
\Abac::addUser2Role(用户id, 角色id(int)|角色名(string));

单独添加权限给用户
\Abac::addUser2Permission(用户id, 权限id(int)|权限名(string));

Blade模板使用
@role('角色名1|角色名2', true)
    {{'打工是不可能打工的，这辈子不可能打工'}}
@endrole

@permission('权限名')
    {{'做生意又不会，只能靠偷这种东西，才能维持现在的生活'}}
@endpermission

@ability('角色名', '权限名', false)
    {{'为什么不去打工，有手有脚的'}}
@endability