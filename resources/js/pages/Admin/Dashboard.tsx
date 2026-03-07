import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/Authenticated';
import AuditLogViewer from '@/components/Domain/AuditLogViewer';
import SystemHealthDashboard from '@/components/Domain/SystemHealthDashboard';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Activity, Shield } from 'lucide-react';

function AdminDashboard() {
    const [activeTab, setActiveTab] = useState('health');

    return (
        <AuthenticatedLayout header={<h2 className="font-semibold text-xl leading-tight">Admin Dashboard</h2>}>
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                        <TabsList className="grid w-full md:w-auto grid-cols-2">
                            <TabsTrigger value="health" className="flex items-center gap-2">
                                <Activity className="h-4 w-4" />
                                System Health
                            </TabsTrigger>
                            <TabsTrigger value="audit" className="flex items-center gap-2">
                                <Shield className="h-4 w-4" />
                                Audit Logs
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="health" className="mt-6">
                            <SystemHealthDashboard />
                        </TabsContent>

                        <TabsContent value="audit" className="mt-6">
                            <AuditLogViewer />
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

export default AdminDashboard;
