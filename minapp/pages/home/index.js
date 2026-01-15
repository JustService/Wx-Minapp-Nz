Page({
  data: {
    resources: {
      points: 120,
      xp: 300,
      level: 2
    },
    quickEntries: [
      { title: '测评中心', route: '/pages/evaluate/index' },
      { title: '成长任务', route: '/pages/growth/index' },
      { title: '权益兑换', route: '/pages/benefits/index' },
      { title: '兴趣搭子', route: '/pages/buddy/index' }
    ],
    tasks: [
      { title: '今日打卡', desc: '完成即可获得积分', status: '未完成' },
      { title: '测评挑战', desc: '完成任意测评', status: '进行中' }
    ],
    notices: ['本周测评完成双倍积分', '兴趣搭子组队任务已上线']
  },
  goTo(e) {
    const route = e.currentTarget.dataset.route;
    if (route) {
      wx.navigateTo({ url: route });
    }
  }
});
