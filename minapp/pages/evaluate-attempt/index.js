Page({
  data: {
    id: null,
    progress: 1,
    total: 3,
    question: {
      stem: '你会在每天固定时间复习吗？',
      options: ['每天都会', '偶尔会', '很少']
    }
  },
  onLoad(query) {
    this.setData({ id: query.id || 1 });
  },
  onNext() {
    wx.navigateTo({ url: `/pages/evaluate-report/index?id=${this.data.id}` });
  }
});
