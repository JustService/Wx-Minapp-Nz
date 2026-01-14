#!/usr/bin/env bash
set -euo pipefail

# 初始化提交并推送到远端（适用于空仓库首次提交）
# 用法：
#   1) git clone https://github.com/JustService/Wx-Minapp-Nz.git
#   2) cd Wx-Minapp-Nz
#   3) 把本仓库文件拷贝进来（或解压 zip 覆盖）
#   4) bash ./push_init.sh

git status

# 确保 main 分支
if git rev-parse --git-dir >/dev/null 2>&1; then
  :
else
  echo "当前目录不是 git 仓库，请先 git clone 再执行"
  exit 1
fi

git add -A

if git diff --cached --quiet; then
  echo "没有可提交的变更（working tree clean）"
  exit 0
fi

git commit -m "docs: add Codex executable spec (OpenAPI + schema + acceptance)"

git branch -M main

git push -u origin main
