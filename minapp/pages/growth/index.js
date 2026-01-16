Page({
  data: {
    tagCategories: ['兴趣', '学习', '校园', '习惯'],
    userTags: ['篮球', '数学刷题', '安静自习', '科幻'],
    partner: {
      avatarText: '搭',
      nickname: '小北',
      grade: '初三',
      commonTagCount: 2,
      commonTags: ['数学刷题', '科幻'],
      reasonSummary: '共同标签 2 个 · 测评结论相近'
    },
    groupTasks: [
      '双人打卡（+20积分）',
      '双人闯关挑战（+30成长值）',
      '校园了解任务（解锁附加奖励）'
    ]
  },
  onShow() {
    if (typeof this.getTabBar === 'function' && this.getTabBar()) {
      this.getTabBar().setData({ selected: 2 });
    }
  },
  onEditTags() {
    wx.showToast({ title: '后续可接入标签编辑', icon: 'none' });
  },
  onSkip() {
    wx.showToast({ title: '已跳过该推荐', icon: 'none' });
  },
  onLike() {
    wx.showToast({ title: '已标记为喜欢', icon: 'none' });
  },
  onBlock() {
    wx.showToast({ title: '已屏蔽该推荐', icon: 'none' });
  },
  onReport() {
    wx.showToast({ title: '已进入举报流程', icon: 'none' });
  },
  onBlacklist() {
    wx.showToast({ title: '已拉黑该用户', icon: 'none' });
  },
  onPresetGreet() {
    wx.showToast({ title: '已使用预设招呼语', icon: 'none' });
  }
});
