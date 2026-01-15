# Changelog

## Unreleased

### 部署步骤

#### 后端（PHP 8.1+）
1. 进入后端目录：`cd backend`。
2. 安装依赖：`composer install`。
3. 准备运行时目录：确认 `backend/storage` 目录可写（用于生成 `data.json` 与 `audit_log.jsonl`）。
4. 启动本地服务：`php -S 0.0.0.0:8000 -t public`。
5. 验证接口：
   - `POST /api/auth/wechat-login` 获取 token。
   - 携带 `Authorization: Bearer <token>` 调用 `/api/tests/{id}/start`、`/api/growth/overview` 等接口。

#### 小程序（微信开发者工具）
1. 打开微信开发者工具并导入项目，选择 `minapp` 目录作为小程序根目录。
2. 确认 `app.json` 中的页面与 tabBar 配置生效。
3. 点击“编译/预览”，检查首页、测评、成长、权益、我的与兴趣搭子页面是否正常展示。
4. 如需联调后端，在小程序中配置请求域名（开发者工具“详情” → “本地设置” → 勾选不校验合法域名，或配置为后端服务地址）。
