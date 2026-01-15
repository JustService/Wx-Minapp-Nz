Page({
  data: {
    topics: [
      { id: 1, name: '学习能力' },
      { id: 2, name: '成长潜力' }
    ],
    activeTopic: 1,
    tests: [
      {
        id: 1,
        title: '学习习惯测评',
        description: '了解你的学习节奏与习惯。',
        reward: '20 积分 / 10 成长值',
        time: '约 5 分钟'
      },
      {
        id: 2,
        title: '成长潜力测评',
        description: '探索你的兴趣与潜力方向。',
        reward: '15 积分 / 12 成长值',
        time: '约 4 分钟'
      }
    ]
  },
  onTopicChange(event) {
    this.setData({ activeTopic: Number(event.currentTarget.dataset.id) });
  },
  onGoDetail(event) {
    const id = event.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/evaluate-detail/index?id=${id}` });
  }
});
