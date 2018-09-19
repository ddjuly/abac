# 关于abac权限管理

不装逼，不做作，不盲目滥用技术，只为实现功能，使用简单，功能齐全👻👻

# 安装

### composer require安装
```
composer require ddjuly/abac
```

### 创建abac表，默认abac_前缀，当然你也可以改成你自己的，打命令会有提示😑
```
php artisan abac.create-table
```

### 添加到服务提供者
1. 在config app.php providers数组中追加
```php
\Abac\AbacServiceProvider::class,
```
2. 在config app.php aliases数组中追加
```php
'Abac' => \Abac\AbacFacade::class,
```

# 如何使用


### 中间件使用
```php
'permission' => \Abac\Middleware\AbacPermission::class,
'role' => \Abac\Middleware\AbacRole::class,
'ability' => \Abac\Middleware\AbacAbility::class,
```

```php
// 路由
Route::get('/home', 'HomeController@index')->name('home')->middleware("permission:权限名");
Route::get('/home', 'HomeController@index')->name('home')->middleware("role:角色名");
Route::get('/home', 'HomeController@index')->name('home')->middleware("ability:权限名");
```
您也可以copy该中间件出来进行自定义，呵呵！

### Laravel坑逼路由终极大法，特么我们路由文件一路达到1k行，哦不，是2k行，这个锅Laravel必须得背
### 终极大法配合权限管理很容易达到高潮
```php
Route::group(['prefix' => 'prefix'],function(){
    if (strlen($_SERVER['REQUEST_URI']) < 4 || strpos($_SERVER['REQUEST_URI'], '/prefix') === false) {
        return;
    }

    $space = 'Mgr';

    $arr = explode('/',explode('?',$_SERVER['REQUEST_URI'])[0]);
    $index = '/' . $arr[2] . '/' . $arr[3];

    $reflectionClass = new ReflectionClass("App\Http\Controllers\{$space}\\". $arr[2] ."Controller");
    $reflectionMethod = $reflectionClass->getMethod($arr[3]);
    $doc = $reflectionMethod->getDocComment();
    preg_match('/@permission(.*?)\n/', $doc, $permission);

    if ($permission && isset($permission[1])) {
        $permission = trim($permission[1]);
        if ($permission) {
            Route::match(['get', 'post'], $index, value(function() use ($arr){
                return "{$space}\\{$arr[2]}Controller@{$arr[3]}";
            }))->middleware("permission:{$permission}");
        } else {
            Route::match(['get', 'post'], $index, value(function() use ($arr){
                return "{$space}\\{$arr[2]}Controller@{$arr[3]}";
            }));
        }
    } else {
        Route::match(['get', 'post'], $index, value(function() use ($arr){
            return "{$space}\\{$arr[2]}Controller@{$arr[3]}";
        }));
    }

});
```
大法需要获取路由到的function中的注释，比如：@permission

当然你可以修改，比如@role、@ability，根据自己的想法去自定义

例子：
```php
/**
 * @permission 供应商管理
 * @return string
 */
public function saveDirectProduct() {
    $field = $this->getField([
        'direct_link' => 'required|string|min:1',
    ]);
}
```

### 添加角色
```php
\Abac::addRole(角色名);
```

### 添加权限
```php
\Abac::addPermission(权限名);
```

### 添加权限到角色中
```php
\Abac::addPermission2Role(角色id(int)|角色名(string), 权限id(int)|权限名(string));
```

### 添加角色到用户
```
\Abac::addUser2Role(用户id, 角色id(int)|角色名(string));
```

### 单独添加权限给用户
```php
\Abac::addUser2Permission(用户id, 权限id(int)|权限名(string));
```

### 删除权限（并删除所有关联关系）
```php
\Abac::delPermission(权限id|权限名);
```

### 删除角色（并删除所有关联关系）
```php
\Abac::delRole(角色id|角色名);
```

### 移除角色中的权限
```php
\Abac::removePermissionOfRole(权限i|d权限名, 角色id|角色名);
```

### 移除用户中独立的权限
```php
\Abac::removePermissionOfUser(用户id, 权限id|权限名);
```

### 移除用户的角色
```php
\Abac::removeRoleOfUser(用户id, 角色id|角色名);
```


### Blade模板使用
```php
@role('角色名1|角色名2', true)
    {{'打工是不可能打工的，这辈子不可能打工'}}
@endrole

@permission('权限名')
    {{'做生意又不会，只能靠偷这种东西，才能维持现在的生活'}}
@endpermission

@ability('角色名', '权限名', false)
    {{'为什么不去打工，有手有脚的'}}
@endability
```
