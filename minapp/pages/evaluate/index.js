Page({
  data: {
    categories: [
      { key: 'subject', label: '学科' },
      { key: 'personality', label: '性格' },
      { key: 'habit', label: '学习习惯' },
      { key: 'campus', label: '校园偏好' }
    ],
    activeCategory: 'subject',
    tests: [
      {
        id: 1,
        category: 'subject',
        title: '学科倾向测评（基础版）',
        time: '3-5分钟',
        reward: '+20积分',
        statusType: 'todo',
        statusText: '未完成',
        actionText: '推荐'
      },
      {
        id: 2,
        category: 'habit',
        title: '学习习惯测评',
        time: '3-5分钟',
        reward: '+20积分',
        statusType: 'done',
        statusText: '已完成',
        actionText: '可复测'
      },
      {
        id: 3,
        category: 'subject',
        title: '班型匹配测评',
        time: '3-5分钟',
        reward: '+20积分',
        statusType: 'done',
        statusText: '已完成',
        actionText: '可复测'
      },
      {
        id: 4,
        category: 'campus',
        title: '校园生活偏好测评',
        time: '3-5分钟',
        reward: '+20积分',
        statusType: 'done',
        statusText: '已完成',
        actionText: '可复测'
      }
    ]
  },
  onShow() {
    if (typeof this.getTabBar === 'function' && this.getTabBar()) {
      this.getTabBar().setData({ selected: 1 });
    }
  },
  onSwitchCategory(e) {
    const key = e.currentTarget.dataset.key;
    if (!key || key === this.data.activeCategory) return;
    this.setData({ activeCategory: key });
  },
  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/evaluate/detail/index?id=${id}` });
  }
});
