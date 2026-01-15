Page({
  data: {
    title: '学习力挑战',
    description: '围绕坚持力、专注力、计划力进行分析',
    questionCount: 3,
    reward: '20积分 + 30成长值'
  },
  start() {
    wx.navigateTo({ url: '/pages/evaluate/attempt/index' });
  }
});
