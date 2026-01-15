Page({
  data: {
    summary: '你的学习潜能极高，适合挑战高阶班型。',
    scores: [
      { label: '坚持力', value: 80 },
      { label: '专注力', value: 70 },
      { label: '计划力', value: 90 }
    ],
    reward: '奖励已到账：20积分 + 30成长值'
  },
  goGrowth() {
    wx.switchTab({ url: '/pages/growth/index' });
  }
});
