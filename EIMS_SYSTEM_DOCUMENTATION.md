# EIMS — Enterprise Infrastructure Management System

## Complete Functional, User, Workflow, and Operational Guide

**Document status:** Reflects the implemented Laravel application as of July 2026  
**System scope:** Organizational infrastructure and asset lifecycle management  
**Primary deployment context:** University or comparable multi-department organization

---

## 1. Purpose of this document

This document explains EIMS from the perspective of its users, administrators, workflow participants, auditors, and future maintainers. It describes:

- What EIMS manages and what it intentionally does not manage.
- The roles in the system and the authority assigned to each role.
- What each user can see and which records are restricted.
- How assets move from registration through assignment, use, maintenance, transfer, return, inspection, and disposal.
- The complete multi-user interactions for normal and exceptional outcomes.
- How notifications, confirmations, audit logs, QR codes, configurable properties, and organizational departments work.
- Important current controls and implementation boundaries.

The workflows below describe the implemented system, not a proposed procurement or financial platform.

---

## 2. What EIMS is

EIMS is a centralized infrastructure and asset management system. It records organizational property, its classification, physical location, condition, custody, operational history, and controlled lifecycle decisions.

It is designed for more than electronic devices. The default infrastructure groups include:

1. ICT and electronic equipment.
2. Furniture and fittings.
3. Buildings and civil infrastructure.
4. Electrical infrastructure.
5. Water and sanitation infrastructure.
6. Vehicles and transport equipment.
7. Laboratory and research equipment.
8. Teaching and classroom equipment.
9. Medical and health equipment.
10. Security and safety equipment.
11. Sports and recreation equipment.
12. Tools and workshop equipment.
13. Kitchen and catering equipment.
14. Library assets.
15. Grounds and agricultural equipment.
16. Intangible assets and software licences.

Each group contains categories. A category determines the tracking mode and the category-specific attributes that apply to an asset.

### 2.1 What EIMS does not do

EIMS is not a purchasing, supplier, tendering, invoicing, or purchase-order system. It does not manage:

- Supplier quotations.
- Purchase requisitions.
- Purchase orders.
- Invoices or supplier payments.
- Tender evaluation.
- General financial accounting.

The Procurement Officer role in EIMS controls property registration, allocation, requests, movements, maintenance spare decisions, and disposal—not external purchasing transactions.

---

## 3. Core design principles

### 3.1 Configurable classification

Assets are classified using:

`Infrastructure Group → Asset Category → Category Attributes → Individual Asset`

This avoids forcing furniture, vehicles, software, buildings, and electronics to share irrelevant fields.

Examples:

- A laptop category may require processor and RAM.
- A vehicle category may use a plate number as an additional property.
- Furniture may use material, dimensions, or colour.
- Software may use licence identifiers and expiry information.

### 3.2 Flexible asset properties

An asset may contain additional name/value properties. These are stored separately from fixed asset columns and allow one asset to have properties that another asset does not have.

Property names and values accept letters, numbers, and spaces. Duplicate property names on one asset are rejected.

### 3.3 Controlled custody

An asset is not considered assigned merely because Procurement selected a recipient. Custody becomes active only after an authorized recipient confirms physical receipt.

### 3.4 Separation of duties

High-impact workflows involve more than one role:

- Maintenance work is reviewed independently.
- Spare requests require Maintenance Review before Procurement action.
- Disposal requires technical review, Procurement approval, custody surrender when applicable, and finalization.
- Receipt confirmation is performed by the receiving party.

### 3.5 Complete traceability

EIMS records workflow events, model changes, authentication activity, permission failures, actor identity, request route, IP address, browser details, old values, new values, and supporting context.

Audit records are append-only. They cannot be edited or deleted through the application model.

---

## 4. Organizational structure

### 4.1 Departments

Every officer belongs to one department. Departments are database records and are not hard-coded into user forms.

Default operational departments include:

- Administrator.
- Procurement.
- Maintenance.
- Maintenance Review and Control.

Administrators can create, search, edit, activate, and deactivate departments. Deactivation removes a department from future active selections without deleting its history.

Departments may:

- Contain many officers.
- Receive departmental asset custody.
- Have one or more authorized receiving officers for departmental handovers.

### 4.2 User identity fields

Each user has:

- Full name.
- Staff number or organizational identifier.
- Institutional email.
- Phone number.
- Role.
- Department.
- Optional organizational unit.
- Optional primary location.
- Account status: active, inactive, or suspended.

Users may update their name, phone number, and password. They cannot update their own email, staff number, department, role, organizational unit, or primary location.

Administrators control institutional identity, department, role, account status, responsibilities, and password resets.

---

## 5. Roles and authority

EIMS uses role-based permissions. Interface visibility is helpful, but backend permission checks remain authoritative.

## 5.1 System Administrator

The System Administrator can:

- View the dashboard and complete asset registry.
- Register assets.
- Edit assets while they are still eligible for editing.
- Print QR and barcode labels.
- View reports and exports.
- Manage users, roles, account statuses, departments, locations assigned to users, and category responsibilities.
- Reset user passwords.
- View the complete audit trail and detailed audit events.
- View dashboard infrastructure groups and categories.

The System Administrator cannot perform Procurement-only operational actions merely because the account is administrative. In particular, the configured administrator role does not assign assets, approve asset requests, process movements, approve spares, or finalize disposal.

This separation prevents the administrator account from becoming an unrestricted operational actor.

## 5.2 Procurement Officer

The Procurement Officer can:

- View and register assets.
- Edit assets before assignment history exists.
- Print labels.
- View and prepare asset assignments.
- Assign an asset to an individual or department.
- View and decide asset transfer and return requests.
- View all normal asset requests.
- Approve or reject asset requests.
- Allocate an available registered asset to an approved request.
- Decide maintenance spare requisitions.
- Record the actual spares and quantities issued.
- Schedule and conduct inspections.
- Propose retirement.
- Approve or reject technically reviewed disposal proposals.
- Finalize an approved disposal.
- View operational reports and exports.
- View dashboard infrastructure groups and categories.

Procurement cannot perform the independent Maintenance Review stage unless separately assigned the corresponding role.

## 5.3 Maintenance Officer

The Maintenance Officer can:

- View the dashboard and registry.
- See infrastructure groups and categories on the dashboard.
- Report an incident for an asset personally assigned to the officer.
- Receive maintenance cases only for categories assigned to that maintenance officer.
- Inspect the reported problem and submit technical work reports.
- Declare technical outcomes such as repaired, repair in progress, awaiting spare, not repaired, beyond repair, or no fault found.
- Declare whether a spare is required.
- Receive review feedback.
- Confirm receipt of issued spares.
- Continue repair through multiple report and spare cycles.
- Finalize an outcome for the reporting officer when the workflow permits.
- Propose retirement for an eligible asset.

A Maintenance Officer cannot review their own work through the Maintenance Review function and cannot approve a spare requisition.

## 5.4 Maintenance Review Officer

The Maintenance Review Officer can:

- View the dashboard, registry, infrastructure groups, and categories.
- See maintenance submissions only for categories assigned to that reviewer.
- Approve or reject maintenance reports with comments.
- Forward approved spare requirements to Procurement.
- Receive Procurement spare decisions.
- Relay Procurement decisions or issued-spare information to the Maintenance Officer.
- Schedule and conduct asset inspections.
- Propose retirement.
- Perform the independent technical review of disposal proposals.
- View reports.

The reviewer does not issue spares and does not make Procurement’s final spare decision.

## 5.5 Staff Member

The Staff Member can:

- View the dashboard and asset registry.
- Submit normal asset requests.
- View only their own asset requests.
- Confirm or reject handovers addressed to them.
- Confirm departmental handovers when selected as an authorized receiver.
- Report maintenance problems for personally assigned assets.
- View maintenance cases in which they are the reporter.
- Request a transfer or return for property under their custody.
- Confirm a repaired asset’s return.
- Propose retirement when they are an authorized custodian of the asset.
- View their own notifications and profile.

Staff members do not see the dashboard Infrastructure Groups or Asset Categories cards.

## 5.6 Auditor

The Auditor can:

- View the dashboard and asset registry.
- View the assignment register.
- View reports and filtered exports.
- View the complete audit event register.
- Open a dedicated human-readable audit detail page.

The Auditor is read-only and cannot register, assign, transfer, repair, approve, or dispose of assets.

Auditors do not see the dashboard Infrastructure Groups or Asset Categories cards.

---

## 6. Visibility and record scoping

### 6.1 Asset registry

All configured roles have `assets.view` and can browse and search the organizational asset registry. The public verification page is separate and exposes only safe verification information using a token.

### 6.2 Requests

- Procurement users with request approval permission see the organizational request queue.
- Other users see only requests they submitted.
- A request detail is limited to the requester or an authorized Procurement user.

### 6.3 Maintenance

A maintenance case is visible when the logged-in user is one of the following:

- The reporting officer.
- The assigned Maintenance Officer.
- A Maintenance Review Officer responsible for the asset’s category.
- A Procurement Officer responsible for spare processing.

### 6.4 Movements

- Procurement users can see organizational movement records.
- Other users see movements they initiated or movements for which they are authorized receivers.
- Movement details use the same restriction.

### 6.5 Handovers

A pending handover appears only to:

- The selected individual recipient; or
- An officer explicitly selected as an authorized receiver for the destination department.

### 6.6 Inspections

- Users with inspection management permission see inspection records and can schedule inspections.
- An assigned inspector may open and complete their inspection.
- Users without inspection management permission see only inspections assigned to them.

### 6.7 Disposal

Disposal visibility is granted to relevant reviewers, approvers, finalizers, the proposer, and the current custodian or authorized departmental receiver.

### 6.8 Reports and audit

- Reports require `reports.view`.
- Audit requires `audit.view`.
- Audit details use the same permission as the audit register.

### 6.9 Classification cards

The Infrastructure Groups and Asset Categories dashboard cards are visible only to:

- System Administrator.
- Procurement Officer.
- Maintenance Officer.
- Maintenance Review Officer.

The controller does not load classification-card data for other roles.

---

## 7. Asset classification and icon behavior

### 7.1 Live data

The dashboard reads active groups and categories directly from the database on each request.

- Groups come from `asset_groups`.
- Categories come from `asset_categories` joined to `asset_groups`.

New active database records appear without changing the dashboard template.

### 7.2 Icon selection

Groups and categories have optional icon fields.

- A recognized icon name displays its registered line icon.
- A missing or unknown icon displays generated initials.
- The database group colour is used as the icon or initials colour.

Fallback initials follow these rules:

- One word: first and last letter. Example: `Laptop → LP`.
- Multiple words: first letters of the first two meaningful words.
- The word `and`, regardless of case, is ignored. Example: `Vehicles and Transport Equipment → VT`.

---

## 8. Asset registration flow

### 8.1 Who registers assets

- Procurement Officer.
- System Administrator.

### 8.2 Registration sequence

1. The user opens **Asset Registry → Register asset**.
2. The user selects an asset category.
3. EIMS loads the category’s applicable attribute definitions.
4. The user enters core identity information such as name, manufacturer, brand, model, serial number, or existing barcode.
5. The user adds optional asset-specific properties as name/value pairs.
6. The user records condition, ownership type, location, acquisition information, warranty, and notes where applicable.
7. EIMS validates required category attributes, uniqueness rules, custom properties, and identifiers.
8. EIMS generates a unique asset tag and verification token.
9. The asset enters `in_stock` lifecycle status.
10. The registration event is written to asset history and the audit trail.

### 8.3 Labels and verification

Authorized users can print:

- The EIMS asset label.
- A QR code.
- A barcode.

Scanning the EIMS QR code opens the token-based public verification page. The page confirms the asset without exposing unrestricted internal workflow data.

An external barcode may also be recorded and used for internal scanning and search.

### 8.4 Editing restriction

An asset may be edited only before assignment history exists. Once assignment history has been created, EIMS locks ordinary asset editing. This preserves the integrity of the item that was approved and handed over.

---

## 9. Asset lifecycle states

Important asset lifecycle states include:

- `in_stock`: registered and available for allocation.
- `reserved`: an assignment has been prepared and receipt confirmation is pending.
- `assigned`: custody was accepted and is active.
- `under_maintenance`: an active maintenance case exists.
- `awaiting_disposal`: disposal was approved and awaits surrender or finalization.
- `retired`: permanently retired using the retirement method.
- `disposed`: finalized through another disposal method.

Lifecycle transitions are controlled by workflows rather than direct arbitrary status editing.

---

## 10. Assignment without an asset request

This is the normal allocation scenario for an asset already registered in stock.

### 10.1 Scenario A: assignment to an individual

1. Procurement opens the assignment interface.
2. Procurement selects an `in_stock` asset.
3. Procurement selects **Individual officer**.
4. Procurement selects the recipient, receiving location, condition, purpose, expected return date if applicable, notes, and included accessories.
5. EIMS creates a `pending_receipt` assignment.
6. The asset becomes `reserved`.
7. The recipient receives a notification.
8. The recipient physically checks the property.
9. The recipient records who handed it over, condition received, whether it is the expected asset, and receipt remarks.

#### Acceptance outcome

1. The recipient selects **Accept** or **Accept with remarks**.
2. The assignment becomes `active`.
3. The asset becomes `assigned`.
4. The recipient becomes the individual custodian.
5. The confirmed location and condition are recorded.
6. Asset history and audit records are created.

#### Rejection outcome

1. The recipient selects **Reject** and provides remarks.
2. The assignment becomes `rejected`.
3. The asset returns to `in_stock`.
4. No active custodian is created.
5. Procurement may correct the allocation and prepare a new assignment.

### 10.2 Scenario B: assignment to a department

1. Procurement selects **Department**.
2. Procurement chooses the destination department.
3. Procurement selects one or more active officers in that department as authorized receivers.
4. EIMS creates the pending assignment and reserves the asset.
5. Every selected receiver can see the handover.
6. One authorized receiver physically verifies and confirms the property.

#### Acceptance outcome

- The department becomes the custodian.
- The confirming officer is recorded.
- Other receiver options are closed after the first valid confirmation.
- The assignment becomes active and the asset becomes assigned.

#### Rejection outcome

- The pending departmental handover is rejected.
- Receiver options are cancelled.
- The asset returns to stock.
- Existing history remains available for review.

---

## 11. Normal asset request flow

Normal asset requests are independent of maintenance spare requisitions.

### 11.1 Submission

1. A Staff Member opens **Asset Requests → New request**.
2. The officer selects a category and optional preferred location.
3. The officer records purpose and justification.
4. Optional preferred properties may be added, such as RAM, size, material, or capacity.
5. EIMS creates a request with status `submitted`.
6. Procurement users receive notifications.

### 11.2 Scenario A: Procurement rejection

1. Procurement opens the request.
2. Procurement selects **Reject** and enters mandatory comments.
3. The request becomes `rejected`.
4. The requester receives the reason through notification and request details.
5. The workflow ends. No asset is reserved or assigned.

### 11.3 Scenario B: Procurement approval and allocation

1. Procurement approves the request with comments.
2. The request becomes `approved`.
3. Procurement selects an available registered asset from the requested category.
4. EIMS validates that the item is `in_stock` and belongs to the requested category.
5. EIMS creates a `pending_receipt` assignment linked to the request.
6. The asset becomes `reserved` and the request becomes `allocated`.
7. The requester receives a handover notification.
8. The requester physically verifies and confirms receipt.

#### Requester accepts

- The assignment becomes active.
- The asset becomes assigned to the requester.
- The request becomes `fulfilled`.

#### Requester rejects

- The assignment becomes rejected.
- The asset returns to stock.
- The request returns to `approved`, allowing Procurement to allocate another suitable asset.

---

## 12. Return and transfer workflow

Only an active custodian, an authorized departmental receiver, or Procurement may initiate a movement.

EIMS prevents parallel active movements for the same assignment.

## 12.1 Return to Procurement

1. The custodian opens the assigned asset and requests a return.
2. The custodian records the reason and reported condition.
3. The movement becomes `pending_procurement`.
4. Procurement receives a notification.

### Return rejected

- Procurement records rejection comments.
- The movement becomes `rejected`.
- The current custody remains active.
- The initiator is notified.

### Return accepted

- Procurement confirms the condition.
- The active assignment becomes `returned`.
- Custody is cleared.
- The asset returns to `in_stock`.
- The movement becomes `completed`.
- The initiator is notified.

## 12.2 Transfer to an individual

1. The current custodian selects **Transfer**.
2. The destination department is selected first to reduce the officer list.
3. The destination officer, location, reason, and condition are selected.
4. If initiated by the custodian, Procurement must approve or reject it.
5. If Procurement initiates the transfer directly, it proceeds to receipt confirmation without a separate self-approval step.

### Procurement rejects

- The movement ends as `rejected`.
- Existing custody remains unchanged.
- The initiator is notified.

### Procurement approves

- The movement becomes `awaiting_receipt`.
- The initiator and receiver are notified.
- The receiver physically inspects the asset.

#### Receiver accepts

- The source assignment is closed as returned.
- A new active individual assignment is created.
- The destination officer becomes custodian.
- Location and condition are updated.
- The movement becomes `completed`.
- Procurement and the initiator are notified.

#### Receiver rejects

- The movement becomes `receipt_rejected`.
- Existing custody remains unchanged.
- Procurement and the initiator receive the reason.

## 12.3 Transfer to a department

The flow is the same as an individual transfer except that:

- The destination is a department.
- One or more officers from that department are selected as authorized receivers.
- Any selected receiver may confirm.
- The first valid acceptance creates departmental custody and closes remaining receiver options.

---

## 13. Maintenance workflow

Maintenance is category-scoped. The system selects a qualified active Maintenance Officer whose assigned categories include the asset category. Reviewers also see only categories assigned to them.

### 13.1 Reporting a problem

1. The custodian opens **Maintenance → Report a problem**.
2. Only assets personally assigned to that officer and currently `assigned` are selectable.
3. The officer records a problem summary, details, and severity.
4. EIMS finds a category-qualified Maintenance Officer.
5. If none exists, submission is rejected with an explanatory error.
6. A case is created with status `assigned`.
7. The asset becomes `under_maintenance`.
8. The Maintenance Officer is notified.

### 13.2 Maintenance Officer assessment

The officer records:

- Technical outcome.
- Findings.
- Work performed.
- Whether a spare is required.
- Detailed spare description when required.

The report becomes `awaiting_review`, and qualified Maintenance Review Officers are notified.

## 13.3 Scenario A: no spare required

### Review approved

1. Maintenance Review approves with comments.
2. The case becomes `review_approved`.
3. The Maintenance Officer is notified.
4. The Maintenance Officer finalizes the outcome as repaired, not repaired, or beyond repair.

#### Repaired

- The case becomes `ready_for_collection`.
- The reporting officer receives collection instructions.
- After physical return, the reporting officer confirms the returning officer, received condition, correct asset, and comments.
- The case becomes `closed`.
- The asset returns to `assigned` with its confirmed condition.

#### Not repaired or beyond repair

- The case becomes `closed`.
- The reporting officer receives the technical reason.
- The notification advises that a replacement may be requested through the independent Asset Requests module.

### Review rejected

1. The report becomes `review_rejected` and the case becomes `review_rejected`.
2. The Maintenance Officer receives the reviewer’s comments.
3. The officer may finalize an unresolved outcome for the reporting officer or proceed according to the corrective feedback allowed by the case state.

## 13.4 Scenario B: spare required

### Maintenance Review rejects

1. The report becomes `review_rejected`.
2. Nothing is sent to Procurement.
3. The Maintenance Officer is notified.
4. The officer finalizes the asset as not repaired or beyond repair with reasons.
5. The reporting officer receives the outcome and may submit a normal replacement request.

### Maintenance Review approves

1. EIMS creates a separate spare requisition with status `pending_procurement`.
2. The case becomes `awaiting_procurement`.
3. Procurement users receive a notification.

#### Procurement rejects the spare

1. Procurement records mandatory comments.
2. The requisition becomes `rejected` and the case becomes `procurement_rejected`.
3. Maintenance Review is notified.
4. Maintenance Review relays the rejection to the Maintenance Officer.
5. The case becomes `procurement_rejected_relayed`.
6. The Maintenance Officer finalizes the unresolved repair.
7. The reporting officer receives the reason and replacement guidance.

#### Procurement approves and issues the spare

1. Procurement approves with comments.
2. The requisition becomes `approved` and the case becomes `spare_approved`.
3. Procurement records the actual items and quantity issued.
4. The requisition becomes `issued`.
5. Maintenance Review is notified.
6. Maintenance Review relays the issued-spare information.
7. The Maintenance Officer receives a notification.
8. The Maintenance Officer confirms physical receipt and records remarks.
9. The requisition becomes `received`; the case becomes `spare_received`.
10. The Maintenance Officer continues repair and submits another technical report.

If another spare is needed, the review and Procurement cycle repeats. There is no fixed one-spare limit; each cycle is separately recorded and audited.

---

## 14. Inspection workflow

Inspections verify physical reality but do not silently rewrite asset records.

### 14.1 Authorized inspectors

Inspection management is granted to:

- Procurement Officer.
- Maintenance Review Officer.

### 14.2 Inspection sequence

1. An authorized user schedules an inspection for a non-retired, non-disposed asset.
2. An authorized inspector and date/time are selected.
3. The inspection is created as `scheduled`.
4. The inspector is notified.
5. The assigned inspector records:
   - Physical status: present, missing, or inaccessible.
   - Assessed condition.
   - Whether recorded location matches.
   - Whether recorded custody matches.
   - Findings.
   - Recommended follow-up and notes.
6. The inspection becomes `completed`.
7. An asset event and audit record are written.

Inspection findings do not automatically change custody, location, maintenance, or disposal state. Required changes must follow the corresponding controlled workflow.

---

## 15. Retirement and disposal workflow

### 15.1 Eligible proposers

A proposal may be initiated by:

- Procurement.
- Maintenance Review.
- The assigned Maintenance Officer where permitted.
- The current individual custodian.
- An authorized receiver for departmental custody.

An asset already retired, disposed, or awaiting disposal cannot receive another active proposal.

### 15.2 Proposal

1. The proposer selects a reason: beyond repair, obsolete, unsafe, missing, end of service, or uneconomical to repair.
2. A detailed justification is required.
3. The proposal becomes `pending_review`.
4. Qualified Maintenance Review users are notified.

### 15.3 Independent technical review

#### Rejected

- The proposal becomes `review_rejected`.
- The proposer receives the reason.
- The asset remains in its previous operational state.

#### Verified

- The proposal becomes `pending_approval`.
- Procurement is notified.

### 15.4 Procurement decision

#### Rejected

- The proposal becomes `approval_rejected`.
- The proposer is notified.
- The disposal workflow ends.

#### Approved asset currently in custody

1. The asset becomes `awaiting_disposal`.
2. The proposal becomes `awaiting_surrender`, except a missing asset does not require physical surrender.
3. The custodian is notified.
4. The custodian confirms surrender with comments.
5. Active custody is closed and cleared.
6. The proposal becomes `ready_for_finalization`.

#### Approved asset not in custody or reported missing

- The proposal moves directly to `ready_for_finalization`.

### 15.5 Finalization

Procurement records:

- Disposal method: retired, scrapped, donated, sold, lost/write-off, or archived.
- Effective date.
- Witness name.
- Final comments.

The proposal becomes `completed`. The asset becomes:

- `retired` when the method is retired; or
- `disposed` for the other final methods.

Custody is cleared, assignment is no longer possible, and the proposer is notified.

Because staffing may be limited, the Procurement Officer who approved a disposal is allowed to finalize it. The approval and finalization remain separate audited actions.

---

## 16. Notifications

Notifications are private to the recipient and appear in:

- The header notification bell.
- The notifications dashboard.

Notifications may link directly to the relevant request, handover, movement, maintenance case, spare requisition, inspection, or disposal.

Users can:

- View recent notifications.
- Filter read and unread notifications.
- Mark one notification as read.
- Mark all notifications as read.

A user cannot open another user’s notification record.

---

## 17. Reports and exports

Authorized roles can view summary counts for:

- Registered assets.
- Available assets.
- Assigned assets.
- Open maintenance cases.
- Scheduled inspections.
- Active disposals.

The complete asset register can be filtered by:

- Search text.
- Category.
- Department.
- Location.
- Lifecycle status.
- Condition.

The filtered result can be exported to CSV. Export values are protected against common spreadsheet formula injection patterns.

---

## 18. Audit and accountability

### 18.1 Events recorded

EIMS records:

- Successful login.
- Failed login.
- Rate-limited login attempts.
- Logout.
- Model creation and updates.
- Workflow actions.
- Successful and failed requests.
- Permission failures.
- Asset lifecycle events.

### 18.2 Audit information

An audit event may contain:

- Event ULID.
- Date and time.
- Actor and identity used.
- Event type and action.
- Module and route.
- HTTP method, path, and status.
- IP address and browser/client details.
- Related record type and identifier.
- Previous values.
- Recorded values.
- Supporting request context.

Sensitive values such as passwords are redacted before storage.

### 18.3 Human-readable details

Audit details open on a dedicated page. Structured values are displayed as labeled cards rather than raw JSON.

### 18.4 Immutability

Audit records are append-only. Application-level update and delete operations throw an exception.

---

## 19. Concurrency and integrity controls

Critical workflows use database transactions and row locking. This prevents competing users from completing the same action twice.

Examples include:

- Two recipients attempting to confirm one handover.
- Two Procurement users allocating the same asset.
- Multiple decisions on one request.
- Parallel transfers for the same active custody.
- Repeated spare decisions.
- Repeated disposal approval or finalization.

The system also checks the expected current status before performing each transition.

---

## 20. Search and register behavior

Operational registers provide server-side search and filters. These include:

- Assets.
- Assignments.
- Returns and transfers.
- Asset requests.
- Maintenance cases.
- Inspections.
- Disposal proposals.
- Users.
- Departments.
- Reports.
- Audit events.

Pagination retains the current query filters.

---

## 21. Authentication and account security

- Users sign in using staff number or institutional email.
- Only active users can authenticate.
- Repeated failed attempts are rate-limited.
- Login attempts and logout are audited.
- Sessions are regenerated after successful authentication.
- Password reset requires a sufficiently strong password.
- A user changing their own password must provide the current password.
- Administrator password resets invalidate remembered sessions.
- Password values are never displayed in audit details.

---

## 22. Current administrative capabilities and boundaries

### Implemented administrative interfaces

- User registration and editing.
- Role assignment.
- Maintenance and review category responsibility assignment.
- User status management.
- Password reset.
- Department creation, search, editing, activation, and deactivation.
- User primary-location selection.
- Asset registration and pre-assignment editing.
- Reports and audit review.

### Data-model capabilities without a complete dedicated management UI

The database and permission model support configurable asset groups, categories, category attributes, organizational units, and locations. Their live values are used by operational forms and dashboard cards. A complete administrator-facing classification/location editor is not yet exposed through dedicated routes in the current implementation.

This distinction is important: the data is configurable and database-driven, but not every configuration entity currently has a finished CRUD screen.

---

## 23. End-to-end operational summary

The normal lifecycle is:

`Register → In stock → Prepare assignment → Confirm receipt → Assigned → Operate → Transfer/Return/Maintain/Inspect → Retire or dispose`

The request-assisted lifecycle is:

`Submit request → Procurement decision → Allocate registered asset → Confirm receipt → Fulfilled request and active custody`

The maintenance lifecycle is:

`Report problem → Qualified maintenance assessment → Independent review → Optional spare decision/issue/relay/receipt → Final technical outcome → User receipt confirmation or closure`

The disposal lifecycle is:

`Propose → Independent technical review → Procurement approval → Custodian surrender when required → Procurement finalization → Permanently retired/disposed`

At every important step, EIMS preserves the actor, decision, comments, timestamps, state transition, and notifications required to explain what happened and who was responsible.

---

## 24. Practical operating rules

1. Register an asset before trying to allocate it.
2. Do not treat a prepared handover as completed custody.
3. Confirm receipt only after physically inspecting the asset.
4. Use normal Asset Requests for replacement property; do not use spare requisitions.
5. Use spare requisitions only inside an active maintenance workflow.
6. Never bypass Maintenance Review for technical maintenance or disposal review.
7. Do not manually change lifecycle status to imitate a completed workflow.
8. Record mandatory rejection and decision reasons clearly.
9. Keep user departments, primary locations, roles, and category responsibilities current.
10. Deactivate obsolete departments or accounts instead of destroying historical records.
11. Use inspection recommendations to start the correct controlled workflow.
12. Review audit details when investigating a disputed or unexpected action.

---

## 25. Conclusion

EIMS provides a controlled, category-aware, multi-user record of organizational infrastructure. Its strongest controls are recipient-confirmed custody, category-scoped maintenance, independent review, explicit Procurement decisions, immutable auditing, and database-driven classifications.

The system is designed so that no single operational action silently changes ownership or permanently removes an asset without leaving a complete and understandable record.
