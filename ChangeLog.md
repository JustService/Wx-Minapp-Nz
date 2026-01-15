# ChangeLog

## 2024-XX-XX

### 本次改动
- 后端框架调整为 ThinkPHP 入口结构，新增基础引导与自动建库/建表逻辑。
- 通过 `config/database.php` 配置数据库账号密码，启动时自动创建数据库并导入 `docs/schema.mysql.sql`。
- 保留现有 API 逻辑与小程序前端骨架不变。

### 部署方法

#### 1. 配置数据库
编辑 `backend/config/database.php`：
```
return [
  'host' => '127.0.0.1',
  'port' => 3306,
  'database' => 'wx_minapp_nz',
  'username' => 'root',
  'password' => 'root',
  'charset' => 'utf8mb4',
];
```

#### 2. 启动后端（会自动建库建表）
```
cd backend
php -S 0.0.0.0:8000 -t public
```
- 启动后将自动使用 `docs/schema.mysql.sql` 创建数据库与表结构。

#### 3. 启动小程序
使用微信开发者工具导入 `minapp/` 目录运行。

#### 4. 测试
```
php backend/tests/api_smoke_test.php
```
