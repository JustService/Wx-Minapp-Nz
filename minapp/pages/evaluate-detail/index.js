Page({
  data: {
    id: null,
    detail: {
      title: '学习习惯测评',
      description: '了解你的学习节奏与习惯。',
      questionCount: 3,
      reward: '20 积分 / 10 成长值',
      time: '约 5 分钟'
    }
  },
  onLoad(query) {
    this.setData({ id: query.id || 1 });
  },
  onStart() {
    wx.navigateTo({ url: `/pages/evaluate-attempt/index?id=${this.data.id}` });
  }
});
