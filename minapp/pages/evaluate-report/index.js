Page({
  data: {
    summary: '你的学习状态稳定，适合挑战更高目标。',
    scores: [
      { label: '学习习惯', value: 80 },
      { label: '规划能力', value: 70 },
      { label: '坚持度', value: 90 }
    ],
    recommendation: '推荐加入衡辰尖子班'
  },
  onBackHome() {
    wx.switchTab({ url: '/pages/home/index' });
  }
});
