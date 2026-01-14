# 招生服务微信小程序 v0.3 — Codex 可执行工程规格（Engineering Spec）

> 来源：根据《招生服务微信小程序 PRD v0.3（题库/成长/权益抽奖/兴趣搭子 + 手游风UI规范）》整理并工程化重写。  
> 目的：将需求转为**可直接用于 Codex 生成代码/生成接口/生成数据库**的“单一事实来源”文档。  
> 适用：微信小程序（C端）+ PHP 后端 + MySQL + 管理后台（B端）。

---

## 0. 给 Codex 的执行指令（务必遵守）

1. **以本文件为唯一需求来源**（Single Source of Truth）。如遇歧义，按“默认决策”处理并在 PR/提交信息里说明。
2. 先实现 **MVP 必交付**（见第 2 节），再实现可选增强。
3. 对所有写操作接口（提交测评/领奖/兑换/抽奖/举报等）：
   - 必须支持 **幂等**（idempotency_key）。
   - 必须写 **审计日志**（谁、何时、改了什么）。
4. 面向未成年人：默认**最小化收集**、默认**隐藏敏感信息**、默认**关闭自由社交**。
5. 产出要求（建议）：
   - 生成后端：路由、控制器、服务层、DAO/Repository、迁移（Migration）、单元测试。
   - 生成 OpenAPI：可用于前后端联调与 Mock。
   - 生成 DB Schema：MySQL 8.0、utf8mb4、InnoDB。
6. 不做范围（MUST NOT）：
   - 不提供陌生人自由聊天（仅互选+组队任务+预设招呼语）。
   - 不做缴费/正式录取流程。
   - 奖品/权益不得影响录取或分班结果。

---

## 1. 项目概览

### 1.1 产品闭环

- 核心闭环：**宣传展示 → 测评 → 报告 → 预约/预报名 → 后台数据看板**
- 粘性闭环：**打卡/任务 → 积分/成长值 → 等级/徽章/权益 → 复访**
- 兴趣搭子：**标签推荐 → 互选 → 组队任务 → 复访**

### 1.2 技术栈建议（可落地默认）

- 小程序：微信小程序原生（或 Taro/uni-app 任选其一，但默认原生）
- 后端：PHP 8.2 + Laravel 10（或 ThinkPHP 8；默认 Laravel）
- DB：MySQL 8.0（utf8mb4）
- 可选：Redis（计数/限流/缓存/幂等锁）、对象存储（海报/素材）

> 默认决策：若未指定，后端选择 **Laravel 10 + MySQL 8 + Redis（可选开关）**。

### 1.3 仓库建议结构（上传 GitHub 后 Codex 直接按此生成）

```text
repo-root/
  README.md
  docs/
    CODEX_SPEC.md                # 本文件（工程规格）
    openapi.yaml                 # 接口契约（OpenAPI 3.0）
    schema.mysql.sql             # MySQL DDL（可选：由 migration 生成）
    acceptance.md                # 验收用例（含Gherkin/清单）
    seed/
      tags.csv                   # 兴趣标签种子数据（可选）
  backend/                       # Laravel 项目
    app/ ...
    database/migrations/ ...
    routes/api.php
    tests/ ...
  miniprogram/                   # 微信小程序
    app.js app.json app.wxss
    pages/
      home/ ...
      evaluate/ ...
      growth/ ...
      benefits/ ...
      buddy/ ...
      me/ ...
  admin/                         # 管理后台（可选：Vue/React 或 Laravel Blade）
```

---

## 2. 交付范围与优先级

### 2.1 MVP 必交付（MUST）

1. **招生闭环**
   - 学校内容展示（首页/学校介绍/班型介绍）
   - 预约/预报名表单提交与后台可导出（支持脱敏）
   - 渠道归因（scene）统计：访问/测评完成/留资

2. **题库与多主题测评**
   - 题库结构：Topic → Test → Question → Option
   - 出卷：固定卷 + 抽题（规则抽 N 题）
   - 随机化：题目顺序随机 + 选项顺序随机
   - attempt_seed + 映射存档（可回放/审计）
   - 报告生成与存档：总分区间映射 + 维度量表（雷达/条形）

3. **成长体系（简化版）**
   - 每日打卡（每日 1 次）
   - 每日任务（≤3 条）
   - 积分（可消费）+ 成长值（升级）
   - 等级称号（V1 先做称号即可）

4. **权益（简化版）**
   - 积分兑换权益券（背包展示）
   - 权益券状态：可用/已用/过期
   - 核销记录

5. **兴趣搭子（低风险版）**
   - 标签编辑 + 系统推荐标签（用户确认）
   - 推荐卡片流（喜欢/跳过/屏蔽）
   - 互选成功（match）
   - 组队任务（无自由聊天，提供预设招呼语）
   - 举报/屏蔽/黑名单与后台处理

6. **UI 规范落地（小程序端）**
   - 主城首页 + 卡片列表 + Dock 导航 + 统一组件（深色大圆角卡片、顶部资源条、网格卡牌等）

### 2.2 可选增强（SHOULD / OPTIONAL）

- 抽奖完整闭环（奖池概率/库存/规则公示/中奖发放与成本风控报表）
- 测评结果复测对比与成长曲线
- 装扮系统（头像框/背景/皮肤）
- 官方兴趣圈（管理员创建圈子与活动报名）

---

## 3. 关键业务规则（必须工程化）

### 3.1 登录与用户身份

- 小程序通过 `wx.login()` 获取 `code`，后端换取 `openid` 并创建/更新 `user_profile`
- 后端返回 `access_token`（JWT 或自定义 token），后续 API 使用 `Authorization: Bearer <token>`
- 用户画像字段：昵称/头像/年级/城市/隐私设置（可选可编辑）

### 3.2 测评随机化与可回放（MUST）

**目标**：每次作答随机，但能回放审计（同一次 attempt 必须固定）。

- `POST /tests/{testId}/start`
  - 生成 `attempt_id`
  - 生成 `attempt_seed`
  - 按 seed 随机：
    - 题目顺序（question_order）
    - 每题选项顺序（option_order）
  - 将映射写入 `attempt_item`（或 attempt.mapping_json）
- `POST /attempts/{attemptId}/submit`
  - 只能提交一次（幂等）
  - 后端按 attempt 内的映射校验答案并计算得分
  - 生成 `report` 并入库，写入奖励（积分/成长值）

> 默认决策：随机算法使用可复现的伪随机（例如 `mt_rand` + seed），并在后端保存最终顺序，前端不自行洗牌。

### 3.3 成长体系与奖励发放（MUST）

- 积分 points：可消费（兑换权益/抽奖券等）
- 成长值 xp：累计用于升级（不消费）
- 所有奖励发放必须写 `points_ledger`（含来源、关联对象、幂等键）
- 反刷：
  - 每日积分上限（配置项）
  - 单任务冷却/每日次数上限
  - 异常频次限制（可选：Redis 限流 + 黑名单）

### 3.4 权益券（MUST）

- 权益券由兑换产生：`redeem_order` → `user_benefit`
- 状态机：
  - `active`（可用）
  - `used`（已用）
  - `expired`（过期，定时任务或查询时计算）
- 核销：
  - 核销码 `benefit_code`（随机、不可预测）
  - 核销动作写 `benefit_redeem_log`（或写入 user_benefit.meta_json）

### 3.5 兴趣搭子（MUST，低风险社交）

- 不展示手机号/微信号/精确位置
- 推荐卡片：返回“共同标签/共同测评”等理由（reason）
- 互选逻辑：
  - A like B；B like A → 生成 `buddy_match` 和 `team`
- 互动：
  - 仅组队任务 + 预设招呼语（静态模板）
  - 不提供自由输入聊天框
- 风控：
  - 举报/拉黑/屏蔽
  - 喜欢次数上限（配置项）
  - 高风险账号不被推荐（后台标记）

### 3.6 抽奖（OPTIONAL，若做必须合规）

- 奖池必须配置库存、概率（或概率区间）、有效期、发放方式
- 必须有规则公示页和开奖记录
- 不得以强制分享换奖励

---

## 4. 页面与路由（小程序）

> 以“底部 Dock 4–5 项”为信息架构：首页、测评、成长、权益、我的。

### 4.1 TabBar（建议）

- 首页（主城）
- 测评中心
- 成长
- 权益
- 我的

### 4.2 页面清单（MVP）

| 页面 | 路由建议 | 核心模块 |
|---|---|---|
| 首页主城 | `/pages/home/index` | 背景图+角色；顶部资源条；主线任务卡；快捷入口；公告/活动卡 |
| 学校介绍 | `/pages/school/index` | 图集/师资/成果/政策/FAQ/联系与地图 |
| 班型详情 | `/pages/class/detail?id=` | 班型详情；适合人群；名额提示；预约入口 |
| 测评中心 | `/pages/evaluate/index` | 主题筛选；测评卡片列表（用时/奖励/状态） |
| 测评详情 | `/pages/evaluate/detail?id=` | 介绍、题数、奖励、开始按钮 |
| 答题页 | `/pages/evaluate/attempt?id=` | 进度条；题目卡片；选项卡；草稿保存 |
| 结果报告 | `/pages/evaluate/report?id=` | Top 结论；维度条/雷达；行动 CTA；奖励弹窗 |
| 报告历史 | `/pages/me/reports` | 历史列表；复测入口；可选对比 |
| 成长 | `/pages/growth/index` | 今日打卡；任务列表；等级进度；徽章墙（可选） |
| 权益 | `/pages/benefits/index` | 积分余额；兑换列表；我的权益券 |
| 兴趣搭子 | `/pages/buddy/index` | 标签编辑；推荐卡片；互选成功；组队任务；举报/屏蔽 |
| 预报名/预约 | `/pages/lead/form?type=` | 留资表单；预约时段；成功页 |

---

## 5. 后台管理端（B 端）模块

> B 端可用 Laravel Nova/Filament/自建 Admin。关键是权限隔离+审计。

- 账号与权限：角色管理、权限分配、登录安全、操作审计
- 内容管理：首页模块/轮播、学校介绍栏目、素材管理、FAQ、联系信息、海报模板
- 班型与名额：班型配置、计划人数、实时预报名人数、阈值提示、开放/暂停/已满
- 题库与测评：主题/测评/题目/选项、抽题规则、版本发布、上下线
- 结果与报告规则：计分规则、维度定义、结果文案模板、班型推荐映射、报告模板
- 任务与成长：任务模板、奖励配置、等级阈值、徽章/稀有度、反刷规则
- 权益与抽奖：权益商品、兑换规则、抽奖活动/奖池库存/概率、发放与核销、中奖记录
- 用户与线索：用户列表、测评记录、预约/预报名线索、跟进状态、导出（可脱敏）
- 数据看板：访问/完成/留资/转化、留存、任务参与、权益成本、渠道归因（scene）
- 风控与客服：举报处理、黑名单、异常行为监控、抽奖/导出审计日志

---

## 6. 数据模型（面向实现的字段级定义）

> 说明：下列为**推荐最小字段集**，足以实现 MVP。可在不破坏兼容的前提下扩展字段。  
> 类型约定：`BIGINT` 自增主键；时间字段 `DATETIME`；字符串 `VARCHAR(255)`；JSON 用 `JSON`。

### 6.1 用户与钱包

**user_profile**
- id (PK)
- openid (UNIQUE, NOT NULL)
- nickname, avatar_url
- grade (nullable), city (nullable)
- privacy_settings (JSON, default `{}`)
- status (`active|banned`, default `active`)
- created_at, updated_at

**user_wallet**
- user_id (PK, FK->user_profile.id)
- points (INT, default 0)
- xp (INT, default 0)
- level (INT, default 1)
- updated_at

**points_ledger**
- id (PK)
- user_id (INDEX)
- delta_points (INT, default 0)
- delta_xp (INT, default 0)
- biz_type (`task|checkin|test|redeem|lottery|admin_adjust`)
- biz_id (BIGINT, nullable)
- idempotency_key (VARCHAR(64), nullable) + UNIQUE(user_id, biz_type, idempotency_key)
- remark (VARCHAR(255), nullable)
- created_at

### 6.2 题库与测评

**topic**
- id, name, description, sort, status (`online|offline`), created_at, updated_at

**test**
- id, topic_id (FK)
- title, description
- mode (`fixed|draw`)
- question_count (INT)
- time_estimate_sec (INT, nullable)
- reward_points (INT, default 0), reward_xp (INT, default 0)
- rule_json (JSON, nullable)  # 抽题规则/计分规则入口
- status (`online|offline`)
- created_at, updated_at

**question**
- id
- test_id (FK, nullable)          # fixed 模式：归属该 test
- topic_id (FK, nullable)         # draw 模式：归属该 topic 题库池
- q_type (`single|multi|scale`)
- stem (TEXT)
- dimension_key (VARCHAR(64), nullable)
- weight (INT, default 1)
- status (`online|offline`)
- created_at, updated_at

**option**
- id, question_id (FK)
- content (VARCHAR(500))
- score (INT, default 0)
- dimension_key (VARCHAR(64), nullable)
- status (`online|offline`)
- created_at, updated_at

**attempt**
- id
- user_id (FK)
- test_id (FK)
- attempt_seed (VARCHAR(64))
- status (`draft|submitted|abandoned`)
- started_at, submitted_at
- created_at, updated_at

**attempt_item**
- id
- attempt_id (FK)
- question_id (FK)
- question_order (INT)
- option_order_json (JSON)   # 选项ID顺序数组，例如 [12,9,10]
- answer_json (JSON, nullable)  # 用户答案：单选=option_id，多选=[...]
- score (INT, default 0)
- created_at, updated_at

**report**
- id
- user_id (FK)
- test_id (FK)
- attempt_id (FK, UNIQUE)
- summary (TEXT)              # 顶部结论摘要
- result_json (JSON)          # 维度得分、区间映射、推荐班型等
- created_at

> 计分默认规则（可配置扩展）：  
> - single/scale：取所选 option.score  
> - multi：所选 option.score 求和  
> - total_score = Σ question_score  
> - dimension_score[dimension_key] = Σ question_score（按 question.dimension_key 聚合）

### 6.3 任务/打卡/等级/徽章

**task_def**
- id
- task_type (`daily|weekly|achievement|checkin`)
- code (VARCHAR(64), UNIQUE)
- title, description
- rule_json (JSON)            # 目标/条件（例如“完成1次测评”）
- reward_points, reward_xp
- cooldown_sec (INT, default 0)
- max_claim_per_day (INT, nullable)
- status (`online|offline`)
- created_at, updated_at

**user_task**
- id
- user_id (INDEX)
- task_def_id (INDEX)
- progress (INT, default 0)
- target (INT, default 1)
- status (`ongoing|claimable|claimed`)
- last_progress_at, claimed_at
- created_at, updated_at

（徽章墙为可选增强，MVP 可不落库；如实现，按 `badge_def/user_badge` 建表。）

### 6.4 权益与兑换

**benefit_def**
- id
- benefit_type (`coupon|lottery_ticket|service|gift`)
- name, description
- cost_points (INT)
- validity_days (INT, nullable)  # 兑换后有效期
- stock (INT, nullable)          # null = 不限
- per_user_limit (INT, nullable)
- status (`online|offline`)
- meta_json (JSON, nullable)
- created_at, updated_at

**redeem_order**
- id
- user_id
- benefit_def_id
- cost_points
- status (`paid|refunded|cancelled`)  # 这里“paid”表示扣积分成功
- idempotency_key (VARCHAR(64))
- created_at
- UNIQUE(user_id, idempotency_key)

**user_benefit**
- id
- user_id
- benefit_def_id
- status (`active|used|expired`)
- benefit_code (VARCHAR(32), UNIQUE)
- expires_at, used_at
- used_meta_json (JSON, nullable)
- created_at, updated_at

### 6.5 兴趣标签与搭子

**tag_def**
- id
- category (`interest|study|campus|habit`)
- name
- status (`online|offline`)
- created_at

**user_tag**
- user_id
- tag_id
- created_at
- PRIMARY KEY(user_id, tag_id)

**buddy_action**
- id
- user_id
- target_user_id
- action (`like|skip|block`)
- created_at
- UNIQUE(user_id, target_user_id)

**buddy_match**
- id
- user_a_id, user_b_id
- matched_at
- status (`active|ended`)
- UNIQUE(LEAST(user_a_id,user_b_id), GREATEST(user_a_id,user_b_id))  # 逻辑唯一（实现时用应用层保证）

**buddy_report**
- id
- reporter_user_id
- target_user_id
- reason (VARCHAR(255))
- detail (TEXT, nullable)
- status (`open|handled|rejected`)
- handled_by, handled_at
- created_at

**team**
- id
- team_type (`buddy`)
- user_a_id, user_b_id
- created_at

**team_task_def**
- id
- title, description
- rule_json (JSON)
- reward_points, reward_xp
- status (`online|offline`)
- created_at

**team_task**
- id
- team_id
- team_task_def_id
- status (`ongoing|completed`)
- progress_json (JSON, nullable)
- completed_at
- created_at

### 6.6 招生与线索

**class_type**
- id
- name, description
- plan_quota (INT, nullable)
- prereg_count (INT, default 0)
- status (`open|paused|full`)
- show_quota (TINYINT, default 0)
- threshold_warn (INT, nullable)  # 预警阈值
- created_at, updated_at

**lead**
- id
- user_id (nullable)               # 允许游客留资（若允许）
- lead_type (`pre_register|appointment`)
- name (VARCHAR(64))
- phone (VARCHAR(32))
- grade (VARCHAR(32), nullable)
- class_type_id (nullable)
- appointment_time (DATETIME, nullable)
- source_scene (VARCHAR(64), nullable)
- follow_status (`new|contacted|converted|invalid`, default `new`)
- meta_json (JSON, nullable)
- created_at, updated_at

**channel_scene**
- id
- scene (VARCHAR(64), UNIQUE)
- description (VARCHAR(255), nullable)
- created_at, updated_at

---

## 7. API 设计（与 OpenAPI 对齐）

> **本仓库建议同时提供 `docs/openapi.yaml`**。若未提供，按下列接口实现并生成 OpenAPI。

### 7.1 通用规范

- BasePath：`/api`
- 认证：Bearer Token（除公开内容接口外）
- 响应格式（统一）：

```json
{ "code": 0, "message": "ok", "data": { } }
```

- 错误码建议：
  - 400 参数错误
  - 401 未登录/Token 无效
  - 403 无权限/被封禁
  - 404 不存在
  - 409 冲突（重复提交/库存不足）
  - 429 频率限制
  - 500 服务错误

### 7.2 认证

- `POST /auth/wechat-login`
  - 入参：`{ "code": "wx_login_code", "scene": "qr_scene(optional)" }`
  - 出参：token + profile + wallet

### 7.3 内容与学校信息（可公开）

- `GET /content/home`
- `GET /content/school`
- `GET /classes`
- `GET /classes/{id}`

### 7.4 测评

- `GET /topics`
- `GET /tests?topic_id=`
- `GET /tests/{id}`
- `POST /tests/{id}/start`（MUST：生成 attempt + 随机映射）
- `POST /attempts/{id}/save`（draft，可选）
- `POST /attempts/{id}/submit`（MUST：幂等 + 奖励）
- `GET /reports`
- `GET /reports/{id}`

### 7.5 成长与任务

- `GET /growth/overview`
- `POST /growth/checkin`（每日一次，幂等）
- `GET /tasks`
- `POST /tasks/{user_task_id}/claim`（幂等）

### 7.6 权益

- `GET /benefits/store`
- `POST /benefits/redeem`（扣积分+发券，幂等）
- `GET /benefits/my`
- `POST /benefits/{id}/use`（核销/标记已用，幂等）

### 7.7 兴趣搭子

- `GET /tags`
- `PUT /me/tags`
- `GET /buddy/recommendations`
- `POST /buddy/action`（like/skip/block）
- `GET /buddy/matches`
- `GET /teams/{id}`
- `GET /teams/{id}/tasks`
- `POST /teams/{id}/tasks/{team_task_id}/complete`（幂等）
- `POST /buddy/report`

### 7.8 线索

- `POST /leads/pre-register`
- `POST /leads/appointment`
- `GET /me/leads`

### 7.9 抽奖（可选）

- `GET /lottery/events`
- `GET /lottery/events/{id}`
- `POST /lottery/events/{id}/draw`（幂等 + 库存原子扣减）
- `GET /lottery/my-records`

---

## 8. 非功能与合规（必须落实到代码）

### 8.1 性能与并发

- 首屏背景图走 CDN、压缩、懒加载
- 宣讲会高峰：
  - 访问类接口缓存（5–30s 皆可）
  - 关键计数用原子更新（MySQL `UPDATE ... SET x=x+1` 或 Redis INCR）
  - 限流：按 IP/用户/scene 维度（可选）

### 8.2 安全与隐私（未成年人）

- 搭子系统：不展示手机号/微信号/精确位置
- 默认允许使用“预设昵称/头像”
- 导出：提供脱敏选项（手机号中间打码）
- 提供隐私说明与注销/删除入口（至少预留后端接口）

### 8.3 抽奖合规（若做）

- 规则页：参与条件、开奖方式、奖项/数量、概率或区间、兑奖方式与有效期
- 严禁诱导分享换奖励

---

## 9. 验收用例（MVP）

> 建议将本节复制到 `docs/acceptance.md`，并补充 Gherkin 自动化/手工用例。

### 9.1 测评

- 随机化正确：同一 attempt 内刷新页面题序与选项顺序不变；不同 attempt 变化
- 可回放：后台可查看 attempt 的题序与选项映射
- 提交幂等：重复 submit 不会重复发放奖励
- 报告存档：报告列表可查看历史记录

### 9.2 招生

- 班型状态正确（open/paused/full），名额提示按配置展示/隐藏
- 预报名/预约可提交，后台可查询、可导出（脱敏）

### 9.3 成长与权益

- 打卡每日一次，重复点击不重复给积分
- 任务领奖幂等，积分上限生效
- 权益兑换扣积分、发券成功；过期/已用状态显示正确；核销记录可追溯

### 9.4 搭子与风控

- 推荐卡片：like/skip/block 行为生效
- 互选成功生成 match 与 team
- 无自由聊天入口
- 举报可提交，后台可处理并记录审计

---

## 10. 默认决策（避免反复问答）

- 若 PRD 未给出具体数值：
  - 每日积分上限：默认 200（可后台配置）
  - 喜欢次数上限：默认 30/日（可配置）
  - token 过期：默认 7 天
  - 权益券有效期：默认 30 天（若 benefit_def.validity_days 为空）
- 计分规则默认采用第 6.2 节所述“option.score 聚合”。
- 报告模板：先实现通用 JSON 模板渲染（summary + dimension + recommendation），运营可在后台配置文案。

---

## 11. 与原 PRD 的对应关系（追溯）

- 业务闭环、角色权限、题库与测评随机化/存档、成长体系、权益抽奖、兴趣搭子与风控、UI 规范与页面结构、非功能与合规、3 周里程碑：均来自 PRD v0.3 原文要求（已工程化拆解）。

