# Contributing

- 规格变更请先修改 `docs/CODEX_SPEC.md` / `docs/openapi.yaml` / `docs/schema.mysql.sql`，再同步实现。
- 提交前确保：
  - OpenAPI 与实际接口一致
  - 数据库字段与迁移一致
  - 关键流程具备幂等与审计
