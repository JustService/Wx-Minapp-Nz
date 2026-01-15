Page({
  data: {
    reports: [
      { id: 1, title: '学习力挑战', date: '2024-03-12' },
      { id: 2, title: '性格潜能', date: '2024-03-10' }
    ]
  },
  goReport(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/evaluate/report/index?id=${id}` });
  }
});
