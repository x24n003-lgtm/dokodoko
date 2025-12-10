import { useState, useEffect } from 'react';
import { MapPin, Clock, Users, RefreshCw } from 'lucide-react';

// サンプルデータ（実際はAPIから取得）
const generateSampleStudents = () => [
  { id: 1, name: '田中太郎', latitude: 35.6762, longitude: 139.6503, timestamp: new Date(Date.now() - 5 * 60 * 1000) },
  { id: 2, name: '佐藤花子', latitude: 35.6895, longitude: 139.6917, timestamp: new Date(Date.now() - 2 * 60 * 1000) },
  { id: 3, name: '山田次郎', latitude: 35.7090, longitude: 139.7319, timestamp: new Date(Date.now() - 15 * 60 * 1000) },
  { id: 4, name: '鈴木美咲', latitude: 35.6580, longitude: 139.7414, timestamp: new Date(Date.now() - 30 * 60 * 1000) },
  { id: 5, name: '高橋健', latitude: 35.6762, longitude: 139.6503, timestamp: new Date(Date.now() - 1 * 60 * 1000) },
  { id: 6, name: '伊藤愛', latitude: 35.6950, longitude: 139.7000, timestamp: new Date() }
];

// 学校の座標（例：東京駅周辺を学校として設定）
const SCHOOL_LOCATION = { latitude: 35.6762, longitude: 139.6503, radius: 0.5 }; // 半径500m

const StudentLocationList = () => {
  const [students, setStudents] = useState(generateSampleStudents());
  const [lastUpdate, setLastUpdate] = useState(new Date());
  const [isLoading, setIsLoading] = useState(false);

  // 距離計算（Haversine formula）
  const calculateDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371; // 地球の半径（km）
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  };

  // 位置状態を判定
  const getLocationStatus = (student) => {
    const distanceToSchool = calculateDistance(
      student.latitude, 
      student.longitude,
      SCHOOL_LOCATION.latitude,
      SCHOOL_LOCATION.longitude
    );
    
    const timeSinceUpdate = (Date.now() - student.timestamp.getTime()) / (1000 * 60); // 分

    // 学校内の場合
    if (distanceToSchool <= SCHOOL_LOCATION.radius) {
      return { status: 'school', color: 'bg-green-500', text: '学校' };
    }
    
    // 更新が古い場合は家と判断
    if (timeSinceUpdate > 20) {
      return { status: 'home', color: 'bg-red-500', text: '自宅' };
    }
    
    // それ以外は移動中
    return { status: 'moving', color: 'bg-yellow-500', text: '移動中' };
  };

  // データ更新（実際のアプリでは定期的にAPIを呼び出し）
  const refreshData = async () => {
    setIsLoading(true);
    // APIコールのシミュレーション
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // ランダムに位置を少し変更（動きをシミュレーション）
    const updatedStudents = students.map(student => ({
      ...student,
      latitude: student.latitude + (Math.random() - 0.5) * 0.01,
      longitude: student.longitude + (Math.random() - 0.5) * 0.01,
      timestamp: Math.random() > 0.7 ? new Date() : student.timestamp
    }));
    
    setStudents(updatedStudents);
    setLastUpdate(new Date());
    setIsLoading(false);
  };

  // 自動更新
  useEffect(() => {
    const interval = setInterval(refreshData, 30000); // 30秒ごと
    return () => clearInterval(interval);
  }, []);

  const statusCounts = students.reduce((acc, student) => {
    const status = getLocationStatus(student).status;
    acc[status] = (acc[status] || 0) + 1;
    return acc;
  }, {});

  return (
    <div className="max-w-4xl mx-auto p-6 bg-gray-50 min-h-screen">
      <div className="bg-white rounded-lg shadow-md p-6 mb-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold text-gray-800 flex items-center">
            <Users className="mr-2" />
            生徒位置情報一覧
          </h1>
          <button
            onClick={refreshData}
            disabled={isLoading}
            className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            <RefreshCw className={`mr-2 w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
            更新
          </button>
        </div>

        {/* 統計情報 */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div className="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
            <div className="flex items-center">
              <div className="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
              <span className="text-green-800 font-medium">学校: {statusCounts.school || 0}人</span>
            </div>
          </div>
          <div className="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
            <div className="flex items-center">
              <div className="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
              <span className="text-yellow-800 font-medium">移動中: {statusCounts.moving || 0}人</span>
            </div>
          </div>
          <div className="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
            <div className="flex items-center">
              <div className="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
              <span className="text-red-800 font-medium">自宅: {statusCounts.home || 0}人</span>
            </div>
          </div>
        </div>

        {/* 最終更新時刻 */}
        <div className="flex items-center text-sm text-gray-600 mb-4">
          <Clock className="mr-1 w-4 h-4" />
          最終更新: {lastUpdate.toLocaleTimeString('ja-JP')}
        </div>
      </div>

      {/* 生徒一覧 */}
      <div className="bg-white rounded-lg shadow-md">
        <div className="p-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-800">生徒一覧（{students.length}人）</h2>
        </div>
        <div className="divide-y divide-gray-200">
          {students.map((student) => {
            const locationInfo = getLocationStatus(student);
            const timeSinceUpdate = Math.floor((Date.now() - student.timestamp.getTime()) / (1000 * 60));
            
            return (
              <div key={student.id} className="p-4 hover:bg-gray-50 transition-colors">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <div className="flex items-center space-x-2">
                      <div className={`w-4 h-4 rounded-full ${locationInfo.color}`}></div>
                      <span className="font-medium text-gray-900">{student.name}</span>
                    </div>
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                      locationInfo.status === 'school' ? 'bg-green-100 text-green-800' :
                      locationInfo.status === 'moving' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-red-100 text-red-800'
                    }`}>
                      {locationInfo.text}
                    </span>
                  </div>
                  
                  <div className="flex items-center text-sm text-gray-500 space-x-4">
                    <span className="flex items-center">
                      <MapPin className="w-4 h-4 mr-1" />
                      {student.latitude.toFixed(4)}, {student.longitude.toFixed(4)}
                    </span>
                    <span className={`${timeSinceUpdate > 10 ? 'text-orange-600' : ''}`}>
                      {timeSinceUpdate}分前
                    </span>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default StudentLocationList;