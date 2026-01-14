# Wx-Minapp-Nz

本仓库为「招生服务微信小程序」的**可执行规格与工程骨架入口**（面向 Codex / Copilot / 人类研发均可）。

## 目录

- `docs/CODEX_SPEC.md`：**工程规格主入口**（按此实现后端/小程序/后台）
- `docs/openapi.yaml`：OpenAPI 3.0 接口契约（可生成 SDK / Mock / 后端路由骨架）
- `docs/schema.mysql.sql`：MySQL 8.0 建表 DDL（可用于初始化或迁移参考）
- `docs/acceptance.md`：MVP 验收用例（测试清单）
- `docs/PRD_v0.3.pdf`：原始 PRD（参考）

## 给 Codex 的指令（示例）

> 以 `docs/CODEX_SPEC.md` 为唯一需求来源；接口以 `docs/openapi.yaml` 为准；数据结构以 `docs/schema.mysql.sql` 为准。
> 先生成后端（Laravel 10 + MySQL），再生成小程序端（按页面清单与接口对接），最后补齐测试与部署说明。

## 快速开始（本地）

> 本仓库当前仅包含规格文件；业务代码可由 Codex 生成，或你自行搭建。

### 初始化（建议）
1. 新建 Laravel 项目（或 ThinkPHP）放到 `backend/`
2. 新建微信小程序项目放到 `minapp/`
3. 新建后台管理端（Vue/React/Blade）放到 `admin/`
4. 将 `docs/schema.mysql.sql` 导入数据库
5. 按 `docs/openapi.yaml` 生成接口与联调

## 许可

未指定（如需开源协议，请补充 LICENSE）。
