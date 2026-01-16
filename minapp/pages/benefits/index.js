Page({
  data: {
    points: 128,
    tabs: [
      { key: 'shop', label: '兑换' },
      { key: 'lottery', label: '抽奖' },
      { key: 'bag', label: '背包' }
    ],
    activeTab: 'shop',
    shopItems: [
      { name: '抽奖券', cost: 20 },
      { name: '开放日预约券', cost: 30 },
      { name: '咨询体验券', cost: 30 },
      { name: '新生礼包券', cost: 40 }
    ],
    coupons: [
      {
        name: '开放日预约券',
        expireAt: '2024-12-31',
        status: 'available',
        statusText: '可用'
      },
      {
        name: '抽奖券 ×2',
        expireAt: '2024-11-30',
        status: 'available',
        statusText: '可用'
      },
      {
        name: '老生回访券',
        expireAt: '2024-05-01',
        status: 'used',
        statusText: '已用'
      },
      {
        name: '早鸟报名券',
        expireAt: '2024-03-01',
        status: 'expired',
        statusText: '过期'
      }
    ]
  },
  onShow() {
    if (typeof this.getTabBar === 'function' && this.getTabBar()) {
      this.getTabBar().setData({ selected: 3 });
    }
  },
  switchTab(e) {
    const key = e.currentTarget.dataset.key;
    if (!key || key === this.data.activeTab) return;
    this.setData({ activeTab: key });
  },
  goRedeem() {
    this.setData({ activeTab: 'shop' });
  },
  onShopItemTap() {
    wx.showToast({ title: '积分兑换流程可在此接入', icon: 'none' });
  },
  onLotteryTap() {
    wx.showToast({ title: '抽奖活动可在此接入', icon: 'none' });
  }
});
