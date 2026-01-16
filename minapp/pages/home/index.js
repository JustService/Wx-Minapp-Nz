Page({
  data: {
    profile: {
      nickname: '同学',
      level: 3
    },
    wallet: {
      points: 128,
      tickets: 2,
      hasTicketRedDot: true
    },
    mainMission: {
      title: '完成首测评，解锁专属报告',
      desc: '进度',
      progress: 1,
      total: 6
    },
    quickEntries: [
      { title: '测评中心', route: '/pages/evaluate/index', color: '#6C8BFF' },
      { title: '成长任务', route: '/pages/growth/index', color: '#52E0C4' },
      { title: '权益背包', route: '/pages/benefits/index', color: '#FFC857' },
      { title: '学校介绍', route: '/pages/school/index', color: '#9B8FD1' }
    ],
    notice: {
      title: '开放日·校园参观预约',
      desc: '本周日 09:00-11:30｜名额有限，先到先得',
      tags: ['公告', '活动']
    }
  },
  onShow() {
    if (typeof this.getTabBar === 'function' && this.getTabBar()) {
      this.getTabBar().setData({ selected: 0 });
    }
  },
  goTo(e) {
    const route = e.currentTarget.dataset.route;
    if (route) {
      wx.navigateTo({ url: route });
    }
  }
});
