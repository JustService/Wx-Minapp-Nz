Page({
  data: {
    topics: ['学科潜能', '性格优势', '兴趣探索'],
    tests: [
      {
        id: 1,
        title: '学习力挑战',
        time: '10分钟',
        reward: '+20积分',
        status: '可开始'
      },
      {
        id: 2,
        title: '性格潜能',
        time: '8分钟',
        reward: '+15积分',
        status: '可开始'
      }
    ]
  },
  goDetail(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/evaluate/detail/index?id=${id}` });
  }
});
