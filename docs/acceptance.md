# 招生服务小程序 v0.3 — MVP 验收用例（Acceptance）

> 对齐 `docs/CODEX_SPEC.md`。建议用于：测试用例、研发自测清单、上线前验收。

---

## 1. 测评（题库/随机化/报告/存档）

### 1.1 开始测评（start）
- [ ] 登录后调用 `POST /tests/{id}/start` 成功返回 `attempt_id`、`attempt_seed`、题目列表（含选项）
- [ ] **同一次 attempt**：刷新/重新进入答题页，题序与选项顺序保持一致
- [ ] **不同 attempt**：两次 start 的题序或选项顺序至少有变化（随机生效）
- [ ] 后台可按 attempt 查询到题序与选项映射（审计可回放）

### 1.2 保存草稿（save）
- [ ] `POST /attempts/{id}/save` 可保存部分答案
- [ ] 重新进入后可恢复已答题目

### 1.3 提交测评（submit）
- [ ] `POST /attempts/{id}/submit` 成功返回报告
- [ ] 提交后 attempt 状态变为 submitted
- [ ] 重复提交同一 attempt（同 idempotency_key）返回相同结果，不重复发奖（幂等）
- [ ] 报告 `result_json` 含总分与维度得分（至少满足 MVP：总分区间映射 + 维度量表）

### 1.4 报告历史
- [ ] `GET /reports` 能列出历史报告（按时间倒序）
- [ ] `GET /reports/{id}` 能查看报告详情
- [ ] 不同测评主题/测评可分别筛选（可选）

---

## 2. 招生闭环（内容/班型/预约/预报名/归因）

### 2.1 内容展示
- [ ] 首页/学校介绍/FAQ/联系方式等内容可加载（可公开接口可不带 token）
- [ ] 背景大图/轮播资源加载正常（CDN/压缩策略由前端实现）

### 2.2 班型与名额
- [ ] `GET /classes` 返回班型列表
- [ ] 班型状态 open/paused/full 显示正确
- [ ] `show_quota=0` 时不展示名额数字；`show_quota=1` 时展示
- [ ] `threshold_warn` 达到时能触发“预警文案/徽章”（前端规则或后端字段均可）

### 2.3 预报名/预约留资
- [ ] `POST /leads/pre-register` 能提交姓名/手机号等字段
- [ ] `POST /leads/appointment` 能提交预约时间
- [ ] 后台能查询线索并可导出（导出需支持脱敏）
- [ ] `scene` 渠道字段能被记录（来自登录/进入时的 scene）

---

## 3. 成长（打卡/任务/积分/等级）

### 3.1 打卡
- [ ] `POST /growth/checkin` 每日只允许成功一次（幂等）
- [ ] 重复打卡不会重复加积分/成长值
- [ ] 打卡会写入积分流水 points_ledger（biz_type=checkin）

### 3.2 任务
- [ ] `GET /tasks` 返回每日任务（≤3）与其它任务（如周/成就，可选）
- [ ] 完成条件满足后任务状态变为 claimable
- [ ] `POST /tasks/{id}/claim` 领取成功，状态变为 claimed
- [ ] 领取幂等：重复 claim（同 idempotency_key）不会重复发奖
- [ ] 每日积分上限生效（超过上限不再增加 points，并返回提示）

### 3.3 等级
- [ ] xp 增加达到阈值后 level 升级
- [ ] 等级称号展示正确（前端展示或后端字段均可）

---

## 4. 权益（兑换/背包/核销/过期）

### 4.1 兑换
- [ ] `GET /benefits/store` 返回权益商品
- [ ] `POST /benefits/redeem`：
  - [ ] 扣除积分
  - [ ] 生成 `redeem_order`
  - [ ] 生成 `user_benefit`（status=active, benefit_code 非空）
  - [ ] 幂等：同 idempotency_key 不重复扣分/不重复发券
- [ ] 库存不足或积分不足时返回 409/400 并有可读 message

### 4.2 背包与状态
- [ ] `GET /benefits/my` 返回权益券列表
- [ ] 过期判断正确：到期后显示 expired
- [ ] `POST /benefits/{id}/use` 核销后状态变 used，并记录 used_at/used_meta

---

## 5. 兴趣搭子（低风险社交）

### 5.1 标签
- [ ] `GET /tags` 返回标签库（按类别）
- [ ] `PUT /me/tags` 更新我的标签成功
- [ ] 标签数量上限（如有）生效

### 5.2 推荐与互选
- [ ] `GET /buddy/recommendations` 返回推荐列表（含 reasons）
- [ ] `POST /buddy/action`：
  - [ ] like/skip/block 行为能被记录
  - [ ] block 后不再推荐该用户
  - [ ] 互相 like 时返回 match=true，并生成 team_id

### 5.3 组队任务与无自由聊天
- [ ] 互选成功后可看到组队任务入口
- [ ] 只存在预设招呼语/固定交互，无自由输入聊天框

### 5.4 举报/风控
- [ ] `POST /buddy/report` 能提交举报
- [ ] 后台能处理举报并记录审计
- [ ] 被封禁用户无法再发起搭子/测评等关键行为（403）

---

## 6. 通用（日志/审计/安全）

- [ ] 所有写操作均记录幂等键并可追溯
- [ ] 后台关键配置（奖池/概率/库存/名额/导出）均写审计日志（如实现）
- [ ] 导出手机号默认脱敏，且需要权限（如实现）
