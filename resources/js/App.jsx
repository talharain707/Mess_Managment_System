import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';

const api = axios.create({ baseURL: '/api' });
const today = new Date().toISOString().slice(0, 10);

const tabs = [
    { id: 'dashboard', label: 'Dashboard', permission: 'view dashboard' },
    { id: 'users', label: 'Sys Users', permission: 'manage users' },
    { id: 'members', label: 'Members', permission: 'manage members' },
    { id: 'expenses', label: 'Expenses', permission: 'manage expenses' },
    { id: 'menu', label: 'Menu Entries', permission: 'manage menu' },
    { id: 'payments', label: 'Payments', permission: 'manage payments' },
];

const money = (value) => new Intl.NumberFormat('en-PK', { style: 'currency', currency: 'PKR', maximumFractionDigits: 0 }).format(Number(value || 0));
const dateValue = (value) => (value ? String(value).slice(0, 10) : today);
const memberBlank = () => ({ name: '', room_no: '', bed_no: '', joined_on: today, monthly_fee: 20000, is_active: true });
const expenseBlank = (categories = []) => ({ expense_category_id: categories[0]?.id || '', spent_on: today, item_name: '', amount: '', notes: '' });
const menuBlank = () => ({ served_on: today, meal_slot: 'dinner', dish_name: '', estimated_cost: '', notes: '' });
const paymentBlank = (members = []) => ({ member_id: members[0]?.id || '', paid_on: today, amount: '', payment_method: 'cash', notes: '' });
const userBlank = () => ({ name: '', email: '', phone: '', password: '', role: 'staff' });

function errorMessage(err, fallback) {
    const errors = err.response?.data?.errors;

    if (errors) {
        const first = Object.values(errors)[0];

        if (Array.isArray(first) && first[0]) {
            return first[0];
        }
    }

    return err.response?.data?.message || fallback;
}

export default function App() {
    const [token, setToken] = useState(localStorage.getItem('mess_token'));
    const [user, setUser] = useState(null);
    const [dashboard, setDashboard] = useState(null);
    const [members, setMembers] = useState([]);
    const [expenses, setExpenses] = useState([]);
    const [menuEntries, setMenuEntries] = useState([]);
    const [payments, setPayments] = useState([]);
    const [categories, setCategories] = useState([]);
    const [sysUsers, setSysUsers] = useState([]);
    const [activeTab, setActiveTab] = useState('dashboard');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [loginForm, setLoginForm] = useState({ email: '', password: '' });
    const [memberForm, setMemberForm] = useState(memberBlank());
    const [expenseForm, setExpenseForm] = useState(expenseBlank());
    const [menuForm, setMenuForm] = useState(menuBlank());
    const [paymentForm, setPaymentForm] = useState(paymentBlank());
    const [userForm, setUserForm] = useState(userBlank());
    const [editing, setEditing] = useState({ members: null, expenses: null, menu: null, payments: null, users: null });
    const [busySection, setBusySection] = useState('');
    const [closingMonth, setClosingMonth] = useState(false);
    const [unlockingMonth, setUnlockingMonth] = useState(false);
    const [selectedMonth, setSelectedMonth] = useState(today.slice(0, 7));
    const [fetchingDashboard, setFetchingDashboard] = useState(false);

    const allowedTabs = useMemo(() => tabs.filter((tab) => (user?.permissions || []).includes(tab.permission)), [user]);

    useEffect(() => {
        if (token) {
            api.defaults.headers.common.Authorization = `Bearer ${token}`;
            if (!dashboard) refresh();
        }
    }, [token]);

    useEffect(() => {
        if (token && dashboard) {
            fetchIsolatedDashboard();
        }
    }, [selectedMonth]);

    async function fetchIsolatedDashboard() {
        setFetchingDashboard(true);
        try {
            const res = await api.get(`/dashboard?month=${selectedMonth}-01`);
            setDashboard(res.data);
        } catch (e) {
            setError('Failed to load selected month dashboard.');
        } finally {
            setTimeout(() => setFetchingDashboard(false), 300);
        }
    }

    useEffect(() => {
        if (allowedTabs.length && !allowedTabs.some((tab) => tab.id === activeTab)) {
            setActiveTab(allowedTabs[0].id);
        }
    }, [allowedTabs, activeTab]);

    function resetEditor(section, nextMembers = members, nextCategories = categories) {
        setEditing((current) => ({ ...current, [section]: null }));

        if (section === 'members') setMemberForm(memberBlank());
        if (section === 'expenses') setExpenseForm(expenseBlank(nextCategories));
        if (section === 'menu') setMenuForm(menuBlank());
        if (section === 'payments') setPaymentForm(paymentBlank(nextMembers));
        if (section === 'users') setUserForm(userBlank());
    }

    async function refresh() {
        setLoading(true);
        setError('');
        try {
            const [meRes, dashboardRes, membersRes, expensesRes, menuRes, paymentsRes, categoriesRes] = await Promise.all([
                api.get('/me'),
                api.get(`/dashboard?month=${selectedMonth}-01`),
                api.get('/members').catch(()=>({data:[]})),
                api.get('/expenses').catch(()=>({data:[]})),
                api.get('/menu-entries').catch(()=>({data:[]})),
                api.get('/payments').catch(()=>({data:[]})),
                api.get('/expense-categories').catch(()=>({data:[]})),
            ]);
            setUser(meRes.data);
            setDashboard(dashboardRes.data);
            setMembers(membersRes.data);
            setExpenses(expensesRes.data);
            setMenuEntries(menuRes.data);
            setPayments(paymentsRes.data);
            setCategories(categoriesRes.data);

            if (meRes.data.permissions.includes('manage users')) {
                const usrRes = await api.get('/users').catch(() => ({ data: [] }));
                setSysUsers(usrRes.data);
            }
        } catch (err) {
            setError(errorMessage(err, 'Session expired. Please login again.'));
            logout(false);
        } finally {
            setLoading(false);
        }
    }

    async function login(event) {
        event.preventDefault();
        setLoading(true);
        setError('');
        try {
            const response = await api.post('/login', loginForm);
            localStorage.setItem('mess_token', response.data.token);
            setToken(response.data.token);
        } catch (err) {
            setError(errorMessage(err, 'Login failed. Please check email and password.'));
            setLoading(false);
        }
    }

    async function logout(callApi = true) {
        if (callApi && token) {
            try { await api.post('/logout'); } catch (err) {}
        }
        localStorage.removeItem('mess_token');
        delete api.defaults.headers.common.Authorization;
        setToken(null);
        setUser(null);
        setDashboard(null);
        setMembers([]);
        setExpenses([]);
        setMenuEntries([]);
        setPayments([]);
        setCategories([]);
        setSysUsers([]);
        setBusySection('');
        setEditing({ members: null, expenses: null, menu: null, payments: null, users: null });
        setMemberForm(memberBlank());
        setExpenseForm(expenseBlank());
        setMenuForm(menuBlank());
        setPaymentForm(paymentBlank());
        setUserForm(userBlank());
        setLoading(false);
    }

    async function handleCloseMonth() {
        if (!window.confirm("Are you sure you want to close this month?")) return;
        setError('');
        setClosingMonth(true);
        try {
            await api.post('/close-month', { month: `${selectedMonth}-01` });
            await refresh();
        } catch (err) {
            setError(errorMessage(err, 'Failed to close month.'));
        } finally {
            setClosingMonth(false);
        }
    }

    async function handleUnlockMonth() {
        if (!window.confirm("Are you sure you want to UNLOCK this month?")) return;
        setError('');
        setUnlockingMonth(true);
        try {
            await api.post('/unlock-month', { month: `${selectedMonth}-01` });
            await refresh();
        } catch (err) {
            setError(errorMessage(err, 'Failed to unlock month.'));
        } finally {
            setUnlockingMonth(false);
        }
    }

    async function saveResource(section, path, payload) {
        setError('');
        setBusySection(section);
        try {
            if (editing[section]) {
                await api.put(`${path}/${editing[section]}`, payload);
            } else {
                await api.post(path, payload);
            }
            resetEditor(section);
            await refresh();
        } catch (err) {
            setError(errorMessage(err, 'Save failed.'));
        } finally {
            setBusySection('');
        }
    }

    async function removeResource(section, path, id, label) {
        if (!window.confirm(`Delete ${label}?`)) return;
        setError('');
        setBusySection(section);
        try {
            await api.delete(`${path}/${id}`);
            if (editing[section] === id) resetEditor(section);
            await refresh();
        } catch (err) {
            setError(errorMessage(err, 'Delete failed.'));
        } finally {
            setBusySection('');
        }
    }

    if (!token) {
        return (
            <div className="app-shell" style={{ backgroundImage: "linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?q=80&w=2000&auto=format&fit=crop')", backgroundSize: 'cover', backgroundPosition: 'center', minHeight: '100vh' }}>
                <div className="auth-shell hero-grid" style={{ marginTop: '0', paddingTop: '4vh' }}>
                    <section className="hero-card">
                        <div className="eyebrow">Mess Management</div>
                        <h1 className="hero-title">Manage Your Mess Efficiently.</h1>
                        <p className="hero-copy">Track daily + fixed expenses, manage member payments, and auto-calculate monthly ledgers in one seamless system.</p>
                    </section>
                    <section className="auth-card">
                        <div className="eyebrow">Secure Access</div>
                        <h2>Login</h2>
                        {error ? <div className="error-banner">{error}</div> : null}
                        <form className="field-grid" onSubmit={login}>
                            <Field label="Email"><input value={loginForm.email} onChange={(e) => setLoginForm({ ...loginForm, email: e.target.value })} /></Field>
                            <Field label="Password"><input type="password" value={loginForm.password} onChange={(e) => setLoginForm({ ...loginForm, password: e.target.value })} /></Field>
                            <button className="btn btn-primary" disabled={loading}>{loading ? 'Signing in...' : 'Enter Dashboard'}</button>
                        </form>
                    </section>
                </div>
            </div>
        );
    }

    return (
        <div className="app-shell">
            <div className="dashboard-shell">
                <div className="header-row">
                    <div>
                        <div className="brand-chip">G-107 Inspired Layout</div>
                        <h1>Mess Control Room</h1>
                        <div className="subtle" style={{ display: 'flex', alignItems: 'center', gap: '10px', marginTop: '6px' }}>
                            <input 
                                type="month" 
                                value={selectedMonth} 
                                onChange={(e) => setSelectedMonth(e.target.value)} 
                                style={{ padding: '2px 8px', borderRadius: '4px', border: '1px solid #ccc', fontSize: '14px', background: 'transparent' }} 
                            />
                            <span>{dashboard?.month} | {user?.name} | {user?.roles?.join(', ')}</span>
                        </div>
                    </div>
                    <button className="btn btn-secondary" onClick={() => logout(true)}>Logout</button>
                </div>
                {error ? <div className="error-banner">{error}</div> : null}
                <div className="nav-tabs">
                    {allowedTabs.map((tab) => <button key={tab.id} className={`nav-tab ${activeTab === tab.id ? 'active' : ''}`} onClick={() => setActiveTab(tab.id)}>{tab.label}</button>)}
                </div>
                {activeTab === 'dashboard' && dashboard ? <Dashboard dashboard={dashboard} closeMonth={handleCloseMonth} closingMonth={closingMonth} unlockMonth={handleUnlockMonth} unlockingMonth={unlockingMonth} isFetching={fetchingDashboard} canManageLedger={(user?.permissions || []).includes('manage payments')} /> : null}
                {activeTab === 'members' ? <section className="content-grid"><FormCard title={editing.members ? 'Edit Member' : 'Add Member'} submitLabel={busySection === 'members' ? 'Saving...' : editing.members ? 'Update Member' : 'Save Member'} onCancel={editing.members ? () => resetEditor('members') : null} onSubmit={(e) => { e.preventDefault(); saveResource('members', '/members', memberForm); }}>
                    <Field label="Name"><input value={memberForm.name} onChange={(e) => setMemberForm({ ...memberForm, name: e.target.value })} /></Field>
                    <Field label="Phone"><input value={memberForm.phone} onChange={(e) => setMemberForm({ ...memberForm, phone: e.target.value })} /></Field>
                    <Field label="Room No"><input value={memberForm.room_no} onChange={(e) => setMemberForm({ ...memberForm, room_no: e.target.value })} /></Field>
                    <Field label="Bed No"><input value={memberForm.bed_no} onChange={(e) => setMemberForm({ ...memberForm, bed_no: e.target.value })} /></Field>
                    <Field label="Monthly Fee"><input type="number" value={memberForm.monthly_fee} onChange={(e) => setMemberForm({ ...memberForm, monthly_fee: e.target.value })} /></Field>
                    <Field label="Opening Balance"><input type="number" value={memberForm.opening_balance} onChange={(e) => setMemberForm({ ...memberForm, opening_balance: e.target.value })} /></Field>
                    <Field label="Join Date"><input type="date" value={dateValue(memberForm.joined_on)} onChange={(e) => setMemberForm({ ...memberForm, joined_on: e.target.value })} /></Field>
                    <Field label="Notes"><textarea rows="3" value={memberForm.notes} onChange={(e) => setMemberForm({ ...memberForm, notes: e.target.value })} /></Field>
                </FormCard><DataTable title="Members" headers={['Name', 'Room', 'Monthly Fee', 'Opening', 'Actions']} rows={members.map((member) => [member.name, `${member.room_no || '-'} / ${member.bed_no || '-'}`, money(member.monthly_fee), money(member.opening_balance), <ActionGroup key={`member-${member.id}`} onEdit={() => { setEditing((current) => ({ ...current, members: member.id })); setMemberForm({ name: member.name || '', room_no: member.room_no || '', bed_no: member.bed_no || '', phone: member.phone || '', monthly_fee: member.monthly_fee || 0, opening_balance: member.opening_balance || 0, joined_on: dateValue(member.joined_on), is_active: member.is_active ?? true, notes: member.notes || '' }); }} onDelete={() => removeResource('members', '/members', member.id, `${member.name} member`)} />])} /></section> : null}
                {activeTab === 'expenses' ? <section className="content-grid"><FormCard title={editing.expenses ? 'Edit Expense' : 'Add Expense'} submitLabel={busySection === 'expenses' ? 'Saving...' : editing.expenses ? 'Update Expense' : 'Save Expense'} onCancel={editing.expenses ? () => resetEditor('expenses') : null} onSubmit={(e) => { e.preventDefault(); saveResource('expenses', '/expenses', expenseForm); }}>
                    <Field label="Category"><select value={expenseForm.expense_category_id} onChange={(e) => setExpenseForm({ ...expenseForm, expense_category_id: e.target.value })}>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></Field>
                    <Field label="Spent On"><input type="date" value={dateValue(expenseForm.spent_on)} onChange={(e) => setExpenseForm({ ...expenseForm, spent_on: e.target.value })} /></Field>
                    <Field label="Item Name"><input value={expenseForm.item_name} onChange={(e) => setExpenseForm({ ...expenseForm, item_name: e.target.value })} /></Field>
                    <Field label="Amount"><input type="number" value={expenseForm.amount} onChange={(e) => setExpenseForm({ ...expenseForm, amount: e.target.value })} /></Field>
                    <Field label="Notes"><textarea rows="3" value={expenseForm.notes} onChange={(e) => setExpenseForm({ ...expenseForm, notes: e.target.value })} /></Field>
                </FormCard><DataTable title="Expense Register" headers={['Date', 'Item', 'Category', 'Amount', 'Actions']} rows={expenses.map((expense) => [dateValue(expense.spent_on), expense.item_name, expense.category?.name, money(expense.amount), <ActionGroup key={`expense-${expense.id}`} onEdit={() => { setEditing((current) => ({ ...current, expenses: expense.id })); setExpenseForm({ expense_category_id: expense.expense_category_id || '', spent_on: dateValue(expense.spent_on), item_name: expense.item_name || '', amount: expense.amount || '', notes: expense.notes || '' }); }} onDelete={() => removeResource('expenses', '/expenses', expense.id, `${expense.item_name} expense`)} />])} /></section> : null}
                {activeTab === 'menu' ? <section className="content-grid"><FormCard title={editing.menu ? 'Edit Menu Plan' : 'Plan Menu'} submitLabel={busySection === 'menu' ? 'Saving...' : editing.menu ? 'Update Menu' : 'Save Menu'} onCancel={editing.menu ? () => resetEditor('menu') : null} onSubmit={(e) => { e.preventDefault(); saveResource('menu', '/menu-entries', menuForm); }}>
                    <Field label="Serve Date"><input type="date" value={dateValue(menuForm.served_on)} onChange={(e) => setMenuForm({ ...menuForm, served_on: e.target.value })} /></Field>
                    <Field label="Meal Slot"><select value={menuForm.meal_slot} onChange={(e) => setMenuForm({ ...menuForm, meal_slot: e.target.value })}><option value="breakfast">Breakfast</option><option value="lunch">Lunch</option><option value="dinner">Dinner</option></select></Field>
                    <Field label="Dish Name"><input value={menuForm.dish_name} onChange={(e) => setMenuForm({ ...menuForm, dish_name: e.target.value })} /></Field>
                    <Field label="Estimated Cost"><input type="number" value={menuForm.estimated_cost} onChange={(e) => setMenuForm({ ...menuForm, estimated_cost: e.target.value })} /></Field>
                    <Field label="Notes"><textarea rows="3" value={menuForm.notes} onChange={(e) => setMenuForm({ ...menuForm, notes: e.target.value })} /></Field>
                </FormCard><DataTable title="Scheduled Menu" headers={['Date', 'Slot', 'Dish', 'Cost', 'Actions']} rows={menuEntries.map((entry) => [dateValue(entry.served_on), entry.meal_slot, entry.dish_name, money(entry.estimated_cost), <ActionGroup key={`menu-${entry.id}`} onEdit={() => { setEditing((current) => ({ ...current, menu: entry.id })); setMenuForm({ served_on: dateValue(entry.served_on), meal_slot: entry.meal_slot || 'dinner', dish_name: entry.dish_name || '', estimated_cost: entry.estimated_cost || '', notes: entry.notes || '' }); }} onDelete={() => removeResource('menu', '/menu-entries', entry.id, `${entry.dish_name} menu entry`)} />])} /></section> : null}
                {activeTab === 'payments' ? <section className="content-grid"><FormCard title={editing.payments ? 'Edit Payment' : 'Receive Payment'} submitLabel={busySection === 'payments' ? 'Saving...' : editing.payments ? 'Update Payment' : 'Save Payment'} onCancel={editing.payments ? () => resetEditor('payments') : null} onSubmit={(e) => { e.preventDefault(); saveResource('payments', '/payments', paymentForm); }}>
                    <Field label="Member"><select value={paymentForm.member_id} onChange={(e) => setPaymentForm({ ...paymentForm, member_id: e.target.value })}>{members.map((member) => <option key={member.id} value={member.id}>{member.name}</option>)}</select></Field>
                    <Field label="Paid On"><input type="date" value={dateValue(paymentForm.paid_on)} onChange={(e) => setPaymentForm({ ...paymentForm, paid_on: e.target.value })} /></Field>
                    <Field label="Amount"><input type="number" value={paymentForm.amount} onChange={(e) => setPaymentForm({ ...paymentForm, amount: e.target.value })} /></Field>
                    <Field label="Method"><select value={paymentForm.payment_method} onChange={(e) => setPaymentForm({ ...paymentForm, payment_method: e.target.value })}><option value="cash">Cash</option><option value="bank">Bank</option><option value="adjustment">Adjustment</option></select></Field>
                    <Field label="Notes"><textarea rows="3" value={paymentForm.notes} onChange={(e) => setPaymentForm({ ...paymentForm, notes: e.target.value })} /></Field>
                </FormCard><DataTable title="Payments" headers={['Date', 'Member', 'Method', 'Amount', 'Actions']} rows={payments.map((payment) => [dateValue(payment.paid_on), payment.member?.name, payment.payment_method, money(payment.amount), <ActionGroup key={`payment-${payment.id}`} onEdit={() => { setEditing((current) => ({ ...current, payments: payment.id })); setPaymentForm({ member_id: payment.member_id || '', paid_on: dateValue(payment.paid_on), amount: payment.amount || '', payment_method: payment.payment_method || 'cash', notes: payment.notes || '' }); }} onDelete={() => removeResource('payments', '/payments', payment.id, `${payment.member?.name || 'member'} payment`)} />])} /></section> : null}
                
                {activeTab === 'users' ? <section className="content-grid"><FormCard title={editing.users ? 'Edit User' : 'Add System User'} submitLabel={busySection === 'users' ? 'Saving...' : editing.users ? 'Update User' : 'Add User'} onCancel={editing.users ? () => resetEditor('users') : null} onSubmit={(e) => { e.preventDefault(); saveResource('users', '/users', userForm); }}>
                    <Field label="Name"><input value={userForm.name} onChange={(e) => setUserForm({ ...userForm, name: e.target.value })} required /></Field>
                    <Field label="Email (Login ID)"><input type="email" value={userForm.email} onChange={(e) => setUserForm({ ...userForm, email: e.target.value })} required /></Field>
                    <Field label="Phone"><input value={userForm.phone} onChange={(e) => setUserForm({ ...userForm, phone: e.target.value })} /></Field>
                    <Field label="Role">
                        <select value={userForm.role} onChange={(e) => setUserForm({ ...userForm, role: e.target.value })}>
                            <option value="admin">Admin (All Access)</option>
                            <option value="manager">Manager</option>
                            <option value="staff">Staff (Limited Access)</option>
                        </select>
                    </Field>
                    <Field label={editing.users ? "Reset Password (Optional)" : "Password"}><input type="password" value={userForm.password ?? ''} onChange={(e) => setUserForm({ ...userForm, password: e.target.value })} required={!editing.users} minLength={6} /></Field>
                </FormCard>
                <div className="table-card" style={{ gridColumn: '1 / -1' }}><h3>System Accounts</h3><div className="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th className="text-right">Actions</th></tr></thead><tbody>
                    {sysUsers.map(u => <tr key={u.id}><td>{u.name}</td><td>{u.email}</td><td><span style={{textTransform:'capitalize', background: u.role==='admin'?'#dcfce7':'#f3f4f6', color: u.role==='admin'?'#166534':'#374151', padding:'2px 6px', borderRadius:'12px', fontSize:'12px', fontWeight:'bold'}}>{u.role}</span></td><td>{u.phone}</td><td className="text-right"><ActionGroup onEdit={() => { setEditing((current) => ({ ...current, users: u.id })); setUserForm({ name: u.name || '', email: u.email || '', phone: u.phone || '', password: '', role: u.role || 'staff' }); }} onDelete={() => removeResource('users', '/users', u.id, u.name)} /></td></tr>)}
                </tbody></table></div></div>
                </section> : null}
            </div>
            <div className="bottom-bar">
                {allowedTabs.map((tab) => <button key={tab.id} className={activeTab === tab.id ? 'active' : ''} onClick={() => setActiveTab(tab.id)}>{tab.label}</button>)}
            </div>
        </div>
    );
}

function Dashboard({ dashboard, closeMonth, closingMonth, unlockMonth, unlockingMonth, isFetching, canManageLedger }) {
    return (
        <div style={{ position: 'relative' }}>
            {isFetching && (
                <div style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(255,255,255,0.7)', zIndex: 10, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: '12px' }}>
                    <div style={{ padding: '12px 24px', background: '#3b82f6', color: '#fff', borderRadius: '24px', fontWeight: 'bold', boxShadow: '0 4px 6px rgba(0,0,0,0.1)' }}>
                        Fetching Month Data...
                    </div>
                </div>
            )}
            {canManageLedger && dashboard.stats.is_closed ? (
                <div className="error-banner" style={{ background: '#3b82f6', color: 'white', borderColor: '#2563eb', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span>This month's ledger is closed. Member liabilities have been locked and forwarded.</span>
                    <button className="btn btn-secondary" style={{ padding: '6px 14px', fontSize: '13px', background: 'white', color: '#1e40af', border: 'none', fontWeight: 'bold' }} onClick={unlockMonth} disabled={unlockingMonth}>
                        {unlockingMonth ? 'Unlocking...' : 'Unlock Month'}
                    </button>
                </div>
            ) : canManageLedger ? (
                <div className="action-row" style={{ textAlign: 'right', marginBottom: 18 }}>
                    <button className="btn btn-primary" onClick={closeMonth} disabled={closingMonth}>
                        {closingMonth ? 'Closing...' : 'Close Month & Forward Ledger'}
                    </button>
                </div>
            ) : null}
            <section className="stats-grid">
                <Stat title="Active Members" value={dashboard.stats.active_members} helper="Current occupied beds" />
                <Stat title="Total Revenue" value={money(dashboard.stats.total_revenue)} helper="Fixed monthly share" />
                <Stat title="Payments Received" value={money(dashboard.stats.payments_received)} helper="Collected this month" />
                <Stat title="Total Expenses" value={money(dashboard.stats.total_expenses)} helper="Daily + fixed heads" />
                <Stat title="Balance" value={money(dashboard.stats.balance)} helper="Collections minus expense" />
                <Stat title="Daily Average" value={money(dashboard.stats.daily_expense_average)} helper="Average expense" />
            </section>
            <section className="content-grid" style={{ marginTop: 18 }}>
                <ListCard title="Weekly Expense" items={dashboard.weekly_expense.map((row) => ({ label: row.label, value: money(row.amount) }))} />
                <ListCard title="Expense Heads" items={dashboard.category_breakdown.map((row) => ({ label: row.name, value: money(row.month_total) }))} />
            </section>
            <section className="ledger-grid" style={{ marginTop: 18 }}>
                <DataTable 
                    title="Member Liability" 
                    headers={['Name', 'Fixed', 'Previous', 'Paid', 'Liability']} 
                    rows={dashboard.member_ledger.map((row) => [
                        row.name, 
                        money(row.monthly_fee), 
                        money(row.previous_balance), 
                        money(row.paid), 
                        <span key="badge" style={{ 
                            padding: '4px 8px', 
                            borderRadius: '12px', 
                            fontSize: '13px', 
                            fontWeight: 'bold',
                            whiteSpace: 'nowrap',
                            backgroundColor: row.actual_liability > 0 ? '#fee2e2' : (row.actual_liability < 0 ? '#dcfce7' : '#f3f4f6'),
                            color: row.actual_liability > 0 ? '#991b1b' : (row.actual_liability < 0 ? '#166534' : '#374151')
                        }}>
                            {money(row.actual_liability)}
                        </span>
                    ])} 
                />
                <ListCard title="Menu Calendar" items={dashboard.menu_entries.map((row) => ({ label: `${row.weekday} ${row.served_on} | ${row.dish_name}`, value: money(row.estimated_cost) }))} />
            </section>
        </div>
    );
}

function FormCard({ title, onSubmit, children, onCancel, submitLabel }) {
    return <div className="panel"><h3>{title}</h3><form className="field-grid" onSubmit={onSubmit}>{children}<div className="form-actions"><button className="btn btn-primary" type="submit">{submitLabel || 'Save'}</button>{onCancel ? <button className="btn btn-secondary" type="button" onClick={onCancel}>Cancel Edit</button> : null}</div></form></div>;
}

function DataTable({ title, headers, rows }) {
    return <div className="table-card"><h3>{title}</h3><div className="table-wrap"><table><thead><tr>{headers.map((header) => <th key={header}>{header}</th>)}</tr></thead><tbody>{rows.map((row, index) => <tr key={index}>{row.map((cell, cellIndex) => <td key={`${index}-${cellIndex}`}>{cell}</td>)}</tr>)}</tbody></table></div></div>;
}

function ListCard({ title, items }) {
    return <div className="table-card"><h3>{title}</h3><div className="mini-list">{items.map((item, index) => <div className="mini-item" key={`${item.label}-${index}`}><span>{item.label}</span><strong>{item.value}</strong></div>)}</div></div>;
}

function Stat({ title, value, helper }) {
    return <div className="stat-card" style={{ padding: 18 }}><h3>{title}</h3><div className="stat-value">{value}</div><div className="subtle">{helper}</div></div>;
}

function Field({ label, children }) {
    return <div className="field"><label>{label}</label>{children}</div>;
}

function ActionGroup({ onEdit, onDelete }) {
    return <div className="action-group"><button className="btn btn-inline" type="button" onClick={onEdit}>Edit</button><button className="btn btn-inline btn-danger" type="button" onClick={onDelete}>Delete</button></div>;
}
