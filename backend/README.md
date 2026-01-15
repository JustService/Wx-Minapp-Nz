# 后端骨架（PHP 8.2）

> 该骨架以 `docs/openapi.yaml` 为接口契约，以 `docs/schema.mysql.sql` 为数据模型参考，实现可运行的 MVP Demo。

## 运行

```bash
php -S 0.0.0.0:8000 -t public
```

启动时会读取 `config/database.php` 自动创建数据库与表结构（参考 `docs/schema.mysql.sql`）。

## 基础测试

```bash
php tests/api_smoke_test.php
```

## 说明
- 数据存储使用 `storage/data.json`（用于 MVP 演示），字段与 SQL Schema 对齐。
- 生产环境可替换为 MySQL + ORM，并复用接口逻辑。
